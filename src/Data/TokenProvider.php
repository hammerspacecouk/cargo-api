<?php
declare(strict_types=1);

namespace App\Data;

use function App\Functions\DateTimes\toMutableDateTime;
use App\Infrastructure\ApplicationConfig;
use App\Data\Database\Entity\UsedActionToken as DbToken;
use App\Data\Database\EntityManager;
use App\Domain\Exception\InvalidTokenException;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Exception\PasetoException;
use ParagonIE\Paseto\JsonToken;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Protocol\Version2;
use ParagonIE\Paseto\ProtocolCollection;
use ParagonIE\Paseto\Purpose;
use ParagonIE\Paseto\Rules\NotExpired;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class TokenProvider
{
    private $applicationConfig;
    private $dateTimeFactory;
    private $entityManager;
    private $logger;

    public function __construct(
        EntityManager $entityManager,
        DateTimeFactory $dateTimeFactory,
        ApplicationConfig $applicationConfig,
        LoggerInterface $logger
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->entityManager = $entityManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->logger = $logger;
    }

    public function makeToken(
        array $claims,
        string $subject,
        string $expiryInterval,
        UuidInterface $id = null
    ): Builder {

        if (!$id) {
            $id = ID::makeNewID(DbToken::class); // todo - don't call the ID class outside of database entities?
        }

        return (new Builder())
            ->setKey($this->applicationConfig->getTokenPrivateKey())
            ->setJti((string) $id)
            ->setSubject($subject)
            ->setVersion(new Version2())
            ->setPurpose(Purpose::local())
            ->setExpiration($this->dateTimeFactory->now()->add(new DateInterval($expiryInterval)))
            ->setClaims($claims);
    }

    public function parseTokenFromString(
        string $tokenString,
        bool $confirmSingleUse = true
    ): JsonToken {
        $tokenString = 'v2.local.' . $tokenString;
        $parser = (new Parser())
            ->setKey($this->applicationConfig->getTokenPrivateKey())
            // Adding rules to be checked against the token
            ->addRule(new NotExpired(toMutableDateTime($this->dateTimeFactory->now())))
            ->setPurpose(Purpose::local())
            // Only allow version 2
            ->setAllowedVersions(ProtocolCollection::v2());

        try {
            $token = $parser->parse($tokenString);
        } catch (PasetoException $ex) {
            throw new InvalidTokenException(
                'Token was tampered with or otherwise invalid or expired: ' . $ex->getMessage()
            );
        }

        if ($confirmSingleUse &&
            $this->entityManager->getUsedActionTokenRepo()->hasBeenUsed($this->uuidFromToken($token))
        ) {
            throw new InvalidTokenException('Token has been invalidated');
        }
        return $token;
    }

    public function markAsUsed(JsonToken $token): void
    {
        $this->entityManager->getUsedActionTokenRepo()->markAsUsed(
            $this->uuidFromToken($token),
            $this->expiryFromToken($token)
        );
    }

    private function uuidFromToken(
        JsonToken $token
    ): UuidInterface {
        return Uuid::fromString($token->get('jti'));
    }

    private function expiryFromToken(
        JsonToken $token
    ): DateTimeImmutable {
        return DateTimeImmutable::createFromMutable($token->getExpiration());
    }
}
