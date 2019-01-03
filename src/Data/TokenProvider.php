<?php
declare(strict_types=1);

namespace App\Data;

use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use App\Infrastructure\ApplicationConfig;
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
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;

class TokenProvider
{
    private const ACTIONS_PATH_PREFIX = '/actions';

    private $applicationConfig;
    private $dateTimeFactory;
    private $uuidFactory;
    private $entityManager;
    private $logger;

    public function __construct(
        EntityManager $entityManager,
        DateTimeFactory $dateTimeFactory,
        UuidFactoryInterface $uuidFactory,
        ApplicationConfig $applicationConfig,
        LoggerInterface $logger
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->entityManager = $entityManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->uuidFactory = $uuidFactory;
        $this->logger = $logger;
    }

    public static function getActionPath(string $tokenClass, DateTimeImmutable $dateTime): string
    {
        // change the action paths every day - but make them a bit unpredictable
        if (!\is_subclass_of($tokenClass, AbstractActionToken::class)) {
            throw new \InvalidArgumentException($tokenClass . ' is not an ActionToken');
        }
        // datetime scoped to the day
        // $day = $dateTime->format('Y-m-d');
        // todo - the date has been removed for now because the routes get cached. Switch to dynamic routing
        $combined = \getenv('APP_SECRET') . $tokenClass;
        return self::ACTIONS_PATH_PREFIX . '/' . \sha1($combined);
    }

    public function makeToken(
        array $claims,
        string $subject,
        string $expiryInterval,
        UuidInterface $id = null
    ): Builder {

        if (!$id) {
            $id = $this->uuidFactory->uuid4();
        }

        return (new Builder())
            ->setKey($this->applicationConfig->getTokenPrivateKey())
            ->setJti((string)$id)
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
            ->addRule(new NotExpired(\DateTime::createFromImmutable($this->dateTimeFactory->now())))
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
            $this->expiryFromToken($token),
        );
    }

    private function uuidFromToken(
        JsonToken $token
    ): UuidInterface {
        return Uuid::fromString($token->getJti());
    }

    private function expiryFromToken(
        JsonToken $token
    ): DateTimeImmutable {
        return DateTimeImmutable::createFromMutable($token->getExpiration());
    }
}
