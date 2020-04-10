<?php
declare(strict_types=1);

namespace App\Data;

use App\Data\Database\EntityManager;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\UsedTokenException;
use App\Domain\ValueObject\Token\AbstractToken;
use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use App\Domain\ValueObject\TokenId;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Exception\PasetoException;
use ParagonIE\Paseto\JsonToken;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\Protocol\Version2;
use ParagonIE\Paseto\ProtocolCollection;
use ParagonIE\Paseto\Purpose;
use ParagonIE\Paseto\Rules\NotExpired;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;

class TokenProvider
{
    private const ACTIONS_PATH_PREFIX = '/actions';

    private $applicationConfig;
    private $dateTimeFactory;
    private $uuidFactory;
    private $entityManager;

    public function __construct(
        EntityManager $entityManager,
        DateTimeFactory $dateTimeFactory,
        UuidFactoryInterface $uuidFactory,
        ApplicationConfig $applicationConfig
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->entityManager = $entityManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->uuidFactory = $uuidFactory;
    }

    public static function getActionPath(string $tokenClass, DateTimeImmutable $dateTime): string
    {
        // change the action paths every day - but make them a bit unpredictable
        if (!\is_subclass_of($tokenClass, AbstractActionToken::class)) {
            throw new \InvalidArgumentException($tokenClass . ' is not an ActionToken');
        }
        // datetime scoped to the day
        $day = $dateTime->format('Y-m-d');
        $combined = \getenv('APP_SECRET') . $tokenClass . $day;
        return \sha1($combined);
    }

    public static function splitToken(string $fullTokenString): array
    {
        $splitPoint = strlen(\sha1('0'));
        $tokenKey = substr($fullTokenString, 0, $splitPoint);
        $tokenString = substr($fullTokenString, $splitPoint);
        return [$tokenKey, $tokenString];
    }

    public function makeToken(
        array $claims,
        string $subject,
        string $expiryInterval,
        TokenId $tokenId = null
    ): Builder {

        if ($tokenId) {
            $id = (string)$tokenId;
        } else {
            $id = $this->uuidFactory->uuid6()->toString();
        }

        return (new Builder())
            ->setKey($this->applicationConfig->getTokenPrivateKey())
            ->setJti($id)
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
        $tokenString = AbstractToken::TOKEN_HEADER . $tokenString;
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
            $this->entityManager->getUsedActionTokenRepo()->hasBeenUsed($this->uuidsFromToken($token))
        ) {
            throw new UsedTokenException('Token has been invalidated');
        }
        return $token;
    }

    public function markAsUsed(JsonToken $token): void
    {
        /** @var UuidInterface[] $ids */
        $ids = $this->uuidsFromToken($token);
        try {
            foreach ($ids as $id) {
                $this->entityManager->getUsedActionTokenRepo()->markAsUsed(
                    $id,
                    $this->expiryFromToken($token),
                );
            }
        } catch (UniqueConstraintViolationException $e) {
            // this should only happen if you tried to use two things simultaneously
            throw new UsedTokenException('Token has been invalidated');
        }
    }

    private function uuidsFromToken(
        JsonToken $token
    ): array {
        return TokenId::toIds($token->getJti());
    }

    private function expiryFromToken(
        JsonToken $token
    ): DateTimeImmutable {
        return DateTimeImmutable::createFromMutable($token->getExpiration());
    }
}
