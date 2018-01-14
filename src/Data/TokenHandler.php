<?php
declare(strict_types=1);

namespace App\Data;

use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\CsrfToken;
use App\Infrastructure\ApplicationConfig;
use App\Data\Database\Entity\Token as DbToken;
use App\Data\Database\EntityManager;
use App\Domain\Exception\ExpiredTokenException;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\MissingTokenException;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Token\AccessToken;
use App\Domain\ValueObject\Token\RefreshToken;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenHandler
{
    public const EXPIRY_REFRESH_TOKEN = self::EXPIRY_TWO_MONTHS;
    public const EXPIRY_ACCESS_TOKEN = self::EXPIRY_ONE_HOUR;
    public const EXPIRY_EMAIL_LOGIN = self::EXPIRY_ONE_HOUR;
    public const EXPIRY_DEFAULT = self::EXPIRY_ONE_DAY;
    public const EXPIRY_CSRF = self::EXPIRY_ONE_HOUR;

    private const EXPIRY_TWO_MONTHS = 'P2M';
    private const EXPIRY_ONE_DAY = 'P1D';
    private const EXPIRY_ONE_HOUR = 'PT1H';

    private const COOKIE_REFRESH_NAME = 'refresh_token';
    private const COOKIE_ACCESS_NAME = 'access_token';

    private $applicationConfig;
    private $currentTime;
    private $entityManager;
    private $logger;

    public function __construct(
        EntityManager $entityManager,
        DateTimeImmutable $currentTime,
        ApplicationConfig $applicationConfig,
        LoggerInterface $logger
    ) {

        $this->applicationConfig = $applicationConfig;
        $this->entityManager = $entityManager;
        $this->currentTime = $currentTime;
        $this->logger = $logger;
    }

    public function makeNewCsrfToken($context): CsrfToken
    {
        return new CsrfToken($this->makeToken(
            CsrfToken::makeClaims($context),
            ID::makeNewID(DbToken::class),
            self::EXPIRY_CSRF
        ));
    }

    public function makeNewRefreshTokenCookie(EmailAddress $emailAddress, string $description)
    {
        $emailAddress = (string) $emailAddress;

        $accessKey = bin2hex(random_bytes(32)); // random key to be the password
        $digest = RefreshToken::secureAccessKey($accessKey); // digest to be stored (accessKey must not be stored)

        $tokenId = ID::makeNewID(DbToken::class);
        $claims = RefreshToken::makeClaims($accessKey);

        $token = $this->makeToken($claims, $tokenId, self::EXPIRY_REFRESH_TOKEN);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $userRepo = $this->entityManager->getUserRepo();
            $user = $userRepo->getByEmail($emailAddress, Query::HYDRATE_OBJECT);
            if (!$user) {
                $this->logger->notice('[NEW PLAYER] Creating a new player');
                $user = $userRepo->createByEmail($emailAddress);
            }

            // store in the database as a valid token
            $tokenEntity = new DbToken(
                $tokenId,
                DbToken::TYPE_REFRESH,
                $this->currentTime,
                $this->currentTime->add(new DateInterval(self::EXPIRY_REFRESH_TOKEN)),
                $user,
                $digest,
                $description
            );
            $this->entityManager->persist($tokenEntity);
            $this->logger->info('Saving');
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Failed to create refresh token. Rollback transaction');
            throw $e;
        }

        return $this->makeRefreshCookie($token);
    }

    public function makeToken(
        array $claims,
        UuidInterface $id = null,
        string $expiry = self::EXPIRY_DEFAULT
    ): Token {
    
        if (!$id) {
            $id = ID::makeNewID(DbToken::class);
        }

        $builder = (new Builder())
            ->setIssuedAt($this->currentTime->getTimestamp())
            ->setId((string)$id)
            ->setExpiration($this->currentTime->add($this->getExpiryInterval($expiry))->getTimestamp());

        foreach ($claims as $key => $value) {
            $builder->set($key, $value);
        }

        // Now that all the data is present we can sign it
        $builder->sign($this->getSigner(), $this->applicationConfig->getTokenPrivateKey());

        return $builder->getToken();
    }

    public function getCsrfTokenFromRequest(Request $request): CsrfToken
    {
        // check to see if it has a valid refresh token
        $csrfToken = $request->get('csrfToken');
        if (!$csrfToken) {
            throw new MissingTokenException('No CSRF token was found');
        }
        return new CsrfToken($this->parseTokenFromString($csrfToken, false));
    }

    public function getRefreshTokenFromRequest(Request $request): RefreshToken
    {
        // check to see if it has a valid refresh token
        $refreshToken = $request->cookies->get(self::COOKIE_REFRESH_NAME);
        if (!$refreshToken) {
            throw new MissingTokenException('No access or refresh token found');
        }
        return new RefreshToken($this->parseTokenFromString($refreshToken, false));
    }

    public function getAccessTokenFromRequest(Request $request): AccessToken
    {
        // check to see if the request already has an access token
        $accessToken = $request->cookies->get(self::COOKIE_ACCESS_NAME) ?? $this->getBearerToken($request);

        if ($accessToken) {
            try {
                $accessToken = $this->parseTokenFromString($accessToken, false);
                return new AccessToken($accessToken);
            } catch (TokenException $e) {
                // ignore this token and carry on to try the refresh token
            }
        }

        $refreshToken = $this->getRefreshTokenFromRequest($request);

        /** @var DbToken $tokenEntity */
        $tokenEntity = $this->entityManager->getTokenRepo()->findRefreshTokenWithUser(
            $refreshToken->getId(),
            Query::HYDRATE_OBJECT
        );
        if (!$tokenEntity) {
            throw new MissingTokenException('Token could not be found');
        }

        // check the password matches
        if (!$refreshToken->validateAccessKey($tokenEntity->digest)) {
            throw new InvalidTokenException('Token could not be verified due to being invalid');
        }

        // generate an access token
        $tokenId = ID::makeNewID(DbToken::class);
        $claims = AccessToken::makeClaims($tokenEntity->user->id);

        $accessToken = $this->makeToken($claims, $tokenId, self::EXPIRY_ACCESS_TOKEN);

        // generate an access cookie
        $accessCookie = $this->makeAccessCookie($accessToken);

        // update the refresh token expiry
        $refreshCookie = $this->extendRefreshToken($refreshToken, $tokenEntity);

        return new AccessToken($accessToken, [
            $accessCookie,
            $refreshCookie,
        ]);
    }

    public function parseTokenFromString(
        string $tokenString,
        $checkIfInvalidated = true
    ): Token {
        try {
            $token = (new Parser())->parse($tokenString);
            return $this->parseToken($token, $checkIfInvalidated);
        } catch (\Exception $e) {
            // turn all types of unrecognised paring errors into InvalidToken errors
            if (!$e instanceof TokenException) {
                $e = new InvalidTokenException($e->getMessage());
            }
            throw $e;
        }
    }

    public function clearCookiesFromResponse(Response $response): Response
    {
        $response->headers->clearCookie(self::COOKIE_ACCESS_NAME, '/', $this->applicationConfig->getCookieScope());
        $response->headers->clearCookie(self::COOKIE_REFRESH_NAME, '/', $this->applicationConfig->getCookieScope());
        return $response;
    }

    private function parseToken(
        Token $token,
        $checkIfInvalidated = true
    ): Token {

        if ($token->isExpired($this->currentTime)) {
            throw new ExpiredTokenException('Token has expired');
        }

            $data = new ValidationData($this->currentTime->getTimestamp());
        if (!$token->verify($this->getSigner(), $this->applicationConfig->getTokenPrivateKey()) ||
                !$token->validate($data)
            ) {
            throw new InvalidTokenException('Token was tampered with or otherwise invalid');
        }

        if ($checkIfInvalidated &&
                !$this->entityManager->getTokenRepo()->isValid($this->uuidFromToken($token))
            ) {
            throw new InvalidTokenException('Token has been invalidated');
        }


        return $token;
    }

    private function getSigner(): Sha256
    {
        return new Sha256();
    }

    private function getExpiryInterval(string $expiry = self::EXPIRY_DEFAULT)
    {
        return new DateInterval($expiry);
    }

    private function makeRefreshCookie(Token $token): Cookie
    {
        return $this->makeCookie(
            (string)$token,
            self::COOKIE_REFRESH_NAME,
            $this->currentTime->add(new DateInterval(self::EXPIRY_REFRESH_TOKEN))
        );
    }

    private function makeCookie(string $content, string $name, ?DateTimeImmutable $expire)
    {
        if (!$expire) {
            $expire = 0; // session cookie
        }

        return new Cookie(
            $name,
            $content,
            $expire,
            '/',
            $this->applicationConfig->getCookieScope(),
            false, // secureCookie - todo - be true as often as possible
            true // httpOnly
        );
    }

    private function getBearerToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader) {
            $parts = explode(' ', trim($authHeader));
            if ($parts[0] === 'Bearer' && isset($parts[1])) {
                return $parts[1];
            }
        }
        return null;
    }

    private function uuidFromToken(
        Token $token
    ): UuidInterface {
    
        return Uuid::fromString($token->getClaim('jti'));
    }

    private function makeAccessCookie(Token $token): Cookie
    {
        return $this->makeCookie(
            (string)$token,
            self::COOKIE_ACCESS_NAME,
            null
        );
    }

    private function extendRefreshToken(RefreshToken $refreshToken, DbToken $tokenEntity)
    {
        $claims = RefreshToken::makeClaims($refreshToken->getAccessKey());
        $token = $this->makeToken($claims, $refreshToken->getId(), self::EXPIRY_REFRESH_TOKEN);

        $tokenEntity->lastUpdate = $this->currentTime;
        $tokenEntity->expiry = $this->currentTime->add(new DateInterval(self::EXPIRY_REFRESH_TOKEN));
        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();

        return $this->makeRefreshCookie($token);
    }

    public function getAccessTokenFromString(string $tokenString): AccessToken
    {
        $accessToken = $this->parseTokenFromString($tokenString, false);
        return new AccessToken($accessToken);
    }

    public function markAsUsed(Token $token): void
    {
        $this->entityManager->getTokenRepo()->markAsUsed(
            $this->uuidFromToken($token),
            $this->expiryFromToken($token)
        );
    }

    public function expireToken(Token $token): void
    {
        $tokenEntity = $this->entityManager->getTokenRepo()->findUnexpiredById(
            $this->uuidFromToken($token),
            Query::HYDRATE_OBJECT
        );
        if (!$tokenEntity) {
            // a token that doesn't exist has already expired. nothing to do
            return;
        }

        $tokenEntity->lastUpdate = $this->currentTime;
        $tokenEntity->expiry = $this->currentTime;
        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();
    }

    private function expiryFromToken(
        Token $token
    ): DateTimeImmutable {
    
        return DateTimeImmutable::createFromFormat('U', (string)$token->getClaim('exp'));
    }
}
