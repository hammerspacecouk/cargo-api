<?php
declare(strict_types = 1);
namespace App\Data;

use App\Config\TokenConfig;
use App\Data\Database\Entity\Token as DbToken;
use App\Data\Database\Entity\User;
use App\Data\Database\EntityManager;
use App\Data\Database\EntityRepository\TokenRepository;
use App\Data\Database\EntityRepository\UserRepository;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\MissingTokenException;
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

class TokenHandler
{
    private const EXPIRY_TWO_MONTHS = 'P2M';
    private const EXPIRY_ONE_DAY = 'P1D';
    private const EXPIRY_ONE_HOUR = 'PT1H';

    private const EXPIRY_REFRESH_TOKEN = self::EXPIRY_TWO_MONTHS;
    private const EXPIRY_ACCESS_TOKEN = self::EXPIRY_ONE_HOUR;
    private const EXPIRY_DEFAULT = self::EXPIRY_ONE_DAY;

    private const COOKIE_REFRESH_NAME = 'refresh_token';
    private const COOKIE_ACCESS_NAME = 'access_token';

    private $tokenConfig;
    private $currentTime;
    private $entityManager;
    private $logger;

    public function __construct(
        EntityManager $entityManager,
        DateTimeImmutable $currentTime,
        TokenConfig $tokenConfig,
        LoggerInterface $logger
    ) {
        $this->tokenConfig = $tokenConfig;
        $this->entityManager = $entityManager;
        $this->currentTime = $currentTime;
        $this->logger = $logger;
    }

    public function makeNewRefreshTokenCookie(string $emailAddress, string $description)
    {
        $accessKey = bin2hex(random_bytes(32)); // random key to be the password
        $digest = RefreshToken::secureAccessKey($accessKey); // digest to be stored (accessKey must not be stored)

        $tokenId = ID::makeNewID(DbToken::class);
        $claims = RefreshToken::makeClaims($accessKey);

        $token = $this->makeToken($claims, $tokenId, self::EXPIRY_REFRESH_TOKEN);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $userRepo = $this->entityManager->getUserRepo();
            $user = $userRepo->getByEmail($emailAddress);
            if (!$user) {
                $this->logger->notice('[NEW PLAYER] Creating a new player');
                $user = $userRepo->createByEmail($emailAddress);
            }

            // store in the database as a valid token
            $tokenEntity = new DbToken(
                $tokenId,
                DbToken::TYPE_REFRESH,
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
            $this->logger->error('Failed to created refresh token. Rollback transaction');
            throw $e;
        }

        return $this->makeRefreshCookie($token);
    }

    public function getAccessTokenFromRequest(Request $request): AccessToken
    {
        // check to see if the request already has an access token
        $accessToken = $request->cookies->get(self::COOKIE_ACCESS_NAME);
        if ($accessToken) {
            try {
                $accessToken = $this->parseTokenFromString($accessToken, false);
                $accessToken = new AccessToken($accessToken);

                // update access token
                $tokenId = $accessToken->getId();
                $claims = AccessToken::makeClaims($accessToken->getUserId());

                $accessToken = $this->makeToken($claims, $tokenId, self::EXPIRY_ACCESS_TOKEN);

                // generate an access cookie
                $accessCookie = $this->makeAccessCookie($accessToken);
                return new AccessToken($accessToken, [$accessCookie]);
            } catch (InvalidTokenException $e) {
                // ignore this token and carry on to try the refresh token
            }
        }

        // check to see if it has a valid refresh token
        $refreshToken = $request->cookies->get(self::COOKIE_REFRESH_NAME);
        if (!$refreshToken) {
            throw new MissingTokenException('No access or refresh token found');
        }

        // fetch the token from the DB to check it exists (and the user exists)
        $refreshToken = new RefreshToken($this->parseTokenFromString($refreshToken, false));

        /** @var DbToken $tokenEntity */
        $tokenEntity = $this->entityManager->getTokenRepo()->findRefreshTokenWithUser($refreshToken->getId(), Query::HYDRATE_OBJECT);
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
            $refreshCookie
        ]);
    }

    public function getAccessTokenFromString(string $tokenString): AccessToken
    {
        $accessToken = $this->parseTokenFromString($tokenString, false);
        return new AccessToken($accessToken);
    }

    public function makeToken(
        array $claims,
        UuidInterface $id = null,
        string $expiry = self::EXPIRY_DEFAULT
    ): Token {
        if (!$id) {
            $id = ID::makeNewID(DbToken::class);
        }

        $signer = new Sha256();
        $builder = (new Builder())
            ->setIssuedAt($this->currentTime->getTimestamp())
            ->setId((string) $id)
            ->setExpiration($this->currentTime->add($this->getExpiryInterval($expiry))->getTimestamp());

        foreach ($claims as $key => $value) {
            $builder->set($key, $value);
        }

        // Now that all the data is present we can sign it
        $builder->sign($signer, $this->tokenConfig->getPrivateKey());

        return $builder->getToken();
    }

    public function parseToken(
        Token $token,
        $checkIfInvalidated = true
    ): Token {
        $data = new ValidationData();
        $data->setCurrentTime($this->currentTime->getTimestamp());

        if ($checkIfInvalidated &&
            !$this->entityManager->getTokenRepo()->isValid($this->uuidFromToken($token))
        ) {
            throw new InvalidTokenException('Token has been invalidated');
        }

        $signer = new Sha256();
        if (!$token->verify($signer, $this->tokenConfig->getPrivateKey()) ||
            !$token->validate($data)
        ) {
            throw new InvalidTokenException('Token was tampered with or expired');
        }
        return $token;
    }

    public function parseTokenFromString(
        string $tokenString,
        $checkIfInvalidated = true
    ): Token {
        $token = (new Parser())->parse($tokenString);
        return $this->parseToken($token, $checkIfInvalidated);
    }

    public function markAsUsed(Token $token): void
    {
        $this->entityManager->getTokenRepo()->markAsUsed(
            $this->uuidFromToken($token),
            $this->expiryFromToken($token)
        );
    }

    private function extendRefreshToken(RefreshToken $refreshToken, DbToken $tokenEntity)
    {
        $claims = RefreshToken::makeClaims($refreshToken->getAccessKey());
        $token = $this->makeToken($claims, $refreshToken->getId(), self::EXPIRY_REFRESH_TOKEN);

        $tokenEntity->expiry = $this->currentTime->add(new DateInterval(self::EXPIRY_REFRESH_TOKEN));
        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();

        return $this->makeRefreshCookie($token);
    }

    private function makeRefreshCookie(Token $token): Cookie
    {
        return $this->makeCookie(
            (string) $token,
            self::COOKIE_REFRESH_NAME,
            $this->currentTime->add(new DateInterval(self::EXPIRY_REFRESH_TOKEN))
        );
    }

    private function makeAccessCookie(Token $token): Cookie
    {
        return $this->makeCookie(
            (string) $token,
            self::COOKIE_ACCESS_NAME,
            null
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
            null, // todo - limit domain scope
            false, // secureCookie - todo - be true as often as possible
            true // httpOnly
        );
    }

    private function getExpiryInterval(string $expiry = self::EXPIRY_DEFAULT)
    {
        return new DateInterval($expiry);
    }

    private function uuidFromToken(
        Token $token
    ): UuidInterface {
        return Uuid::fromString($token->getClaim('jti'));
    }

    private function expiryFromToken(
        Token $token
    ): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat('U', (string) $token->getClaim('exp'));
    }

    private function getTokenRepo(): TokenRepository
    {
        return $this->entityManager->getRepository(DbToken::class);
    }

    private function getUserRepo(): UserRepository
    {
        return $this->entityManager->getRepository(User::class);
    }
}
