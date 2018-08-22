<?php
declare(strict_types=1);

namespace App\Data;

use App\Infrastructure\ApplicationConfig;
use App\Data\Database\Entity\UsedActionToken as DbToken;
use App\Data\Database\EntityManager;
use App\Domain\Exception\ExpiredTokenException;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\TokenException;
use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class TokenProvider
{
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

    public function makeToken(
        array $claims,
        string $expiryInterval,
        UuidInterface $id = null
    ): Token {

        if (!$id) {
            $id = ID::makeNewID(DbToken::class); // todo - don't call the ID class outside of database entities?
        }

        $builder = (new Builder())
            ->setIssuedAt($this->currentTime->getTimestamp())
            ->setId((string)$id)
            ->setExpiration($this->currentTime->add(
                new DateInterval($expiryInterval)
            )->getTimestamp());

        foreach ($claims as $key => $value) {
            $builder->set($key, $value);
        }

        // Now that all the data is present we can sign it
        $builder->sign($this->getSigner(), $this->applicationConfig->getTokenPrivateKey());

        return $builder->getToken();
    }

    public function parseTokenFromString(
        string $tokenString,
        bool $confirmSingleUse = true
    ): Token {
        try {
            $token = (new Parser())->parse($tokenString);
            return $this->parseToken($token, $confirmSingleUse);
        } catch (\Exception $e) {
            // turn all types of unrecognised paring errors into InvalidToken errors
            if (!$e instanceof TokenException) {
                $e = new InvalidTokenException($e->getMessage());
            }
            throw $e;
        }
    }

    public function markAsUsed(Token $token): void
    {
        $this->entityManager->getUsedActionTokenRepo()->markAsUsed(
            $this->uuidFromToken($token),
            $this->expiryFromToken($token)
        );
    }

    private function parseToken(
        Token $token,
        bool $confirmSingleUse = true
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

        if ($confirmSingleUse &&
            $this->entityManager->getUsedActionTokenRepo()->hasBeenUsed($this->uuidFromToken($token))
        ) {
            throw new InvalidTokenException('Token has been invalidated');
        }
        return $token;
    }

    private function getSigner(): Sha256
    {
        return new Sha256();
    }

    private function uuidFromToken(
        Token $token
    ): UuidInterface {
        return Uuid::fromString($token->getClaim('jti'));
    }

    private function expiryFromToken(
        Token $token
    ): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat('U', (string)$token->getClaim('exp'));
    }
}
