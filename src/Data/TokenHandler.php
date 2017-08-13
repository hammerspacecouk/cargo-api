<?php
declare(strict_types = 1);
namespace App\Data;

use App\Config\TokenConfig;
use App\Data\Database\Entity\InvalidToken as DbInvalidToken;
use App\Data\Database\EntityRepository\InvalidTokenRepository;
use App\Domain\Exception\InvalidTokenException;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class TokenHandler
{
    // todo - make private
    public const EXPIRY_ONE_DAY = 'P1D';
    public const EXPIRY_ONE_HOUR = 'PT1H';
    public const EXPIRY_ONE_MINUTE = 'PT1M';
    public const EXPIRY_DEFAULT = self::EXPIRY_ONE_HOUR;

    private const COOKIE_REFRESH_NAME = 'token';
    private const COOKIE_ACCESS_NAME = 'access_token';

    private $tokenConfig;
    private $currentTime;
    private $entityManager;

    public function __construct(
        EntityManager $entityManager,
        DateTimeImmutable $currentTime,
        TokenConfig $tokenConfig
    ) {
        $this->tokenConfig = $tokenConfig;
        $this->entityManager = $entityManager;
        $this->currentTime = $currentTime;
    }

    public function makeToken(
        array $claims,
        string $expiry = self::EXPIRY_DEFAULT
    ): Token {
        $signer = new Sha256();
        $builder = (new Builder())->setIssuer($this->tokenConfig->getIssuer())
            ->setAudience($this->tokenConfig->getAudience())
            ->setIssuedAt($this->currentTime->getTimestamp())
            ->setId(ID::makeNewID(DbInvalidToken::class))
            ->setExpiration($this->currentTime->add($this->getExpiry($expiry))->getTimestamp());

        foreach ($claims as $key => $value) {
            $builder->set($key, $value);
        }

        // Now that all the data is present we can sign it
        $builder->sign($signer, $this->tokenConfig->getPrivateKey());

        return $builder->getToken();
    }

    public function parseToken(
        Token $token
    ): Token {
        $data = new ValidationData();
        $data->setIssuer($this->tokenConfig->getIssuer());
        $data->setAudience($this->tokenConfig->getAudience());
        $data->setCurrentTime($this->currentTime->getTimestamp());

        $signer = new Sha256();
        if (!$token->verify($signer, $this->tokenConfig->getPrivateKey()) ||
            !$token->validate($data) ||
            $this->tokenIsInvalidated($token)) {
            throw new InvalidTokenException('Token was tampered with or expired');
        }
        return $token;
    }

    public function tokenIsInvalidated(Token $token): bool
    {
        return $this->getInvalidTokenRepo()->isInvalid($this->uuidFromToken($token));
    }

    public function markAsUsed(Token $token): void
    {
        $id = $this->uuidFromToken($token);
        $expiry = (new DateTimeImmutable())->setTimestamp($token->getClaim('exp'));
        $this->getInvalidTokenRepo()->markAsUsed($id, $expiry);
    }

    public function parseTokenFromString(
        string $tokenString
    ): Token {
        $token = (new Parser())->parse($tokenString);
        return $this->parseToken($token);
    }

    // todo - move some of this into a service. Controllers shouldn't be looking in the Data folder
    public function makeRefreshTokenCookie(Token $token): Cookie
    {
        $secureCookie = false; // todo - be true as often as possible
        return new Cookie(
            self::COOKIE_REFRESH_NAME,
            (string) $token,
            $this->currentTime->add(new \DateInterval('P2Y')),
            '/',
            null,
            $secureCookie,
            true
        );
    }

    public function getRefreshToken(Request $request): Token
    {
        $tokenString = $request->cookies->get(self::COOKIE_REFRESH_NAME);

        // todo - also possible to get it out of the Auth header
        if (!$tokenString) {
            throw new InvalidTokenException('No credentials');
        }
        $token = $this->parseTokenFromString($tokenString);

    }

    private function getExpiry(string $expiry = self::EXPIRY_DEFAULT)
    {
        return new DateInterval($expiry);
    }

    private function getInvalidTokenRepo(): InvalidTokenRepository
    {
        return $this->entityManager->getRepository(DbInvalidToken::class);
    }

    private function uuidFromToken(
        Token $token
    ): UuidInterface {
        return Uuid::fromString($token->getClaim('jti'));
    }
}