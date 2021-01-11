<?php
declare(strict_types=1);

namespace App\Data;

use App\Data\Database\EntityManager;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\UsedTokenException;
use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use App\Domain\ValueObject\TokenId;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;

class TokenProvider
{
    private ApplicationConfig $applicationConfig;
    private UuidFactoryInterface $uuidFactory;
    private EntityManager $entityManager;

    public function __construct(
        EntityManager $entityManager,
        UuidFactoryInterface $uuidFactory,
        ApplicationConfig $applicationConfig
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->entityManager = $entityManager;
        $this->uuidFactory = $uuidFactory;
    }

    public static function getActionPath(string $tokenClass, DateTimeImmutable $dateTime = null): string
    {
        if (!$dateTime) {
            $dateTime = DateTimeFactory::now();
        }

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
    ): Plain {

        if ($tokenId) {
            $id = (string)$tokenId;
        } else {
            $id = $this->uuidFactory->uuid6()->toString();
        }

        $config = $this->getSymmetricConfig();
        $token = $config->builder()
            ->identifiedBy($id)
            ->relatedTo($subject)
            ->expiresAt(DateTimeFactory::now()->add(new DateInterval($expiryInterval)));

        foreach ($claims as $claim => $value) {
            $token = $token->withClaim($claim, $value);
        }
        return $token->getToken($config->signer(), $config->signingKey());
    }

    public function parseTokenFromString(
        string $tokenString,
        bool $confirmSingleUse = true
    ): Plain {
        $config = $this->getSymmetricConfig();
        try {
            /** @var Plain $token */
            $token = $config->parser()->parse($tokenString);
            $config->validator()->assert($token, ...$config->validationConstraints());
        } catch (RequiredConstraintsViolated | InvalidTokenStructure | CannotDecodeContent $ex) {
            throw new InvalidTokenException(
                'Token was tampered with or otherwise invalid or expired: ' . $ex->getMessage() . $tokenString
            );
        }

        if ($confirmSingleUse &&
            $this->entityManager->getUsedActionTokenRepo()->hasBeenUsed($this->uuidsFromToken($token))
        ) {
            throw new UsedTokenException('Token has been invalidated');
        }
        return $token;
    }

    public function markAsUsed(Plain $token): void
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
        Plain $token
    ): array {
        return TokenId::toIds($token->claims()->get(RegisteredClaims::ID));
    }

    private function expiryFromToken(
        Plain $token
    ): DateTimeImmutable {
        return $token->claims()->get(RegisteredClaims::EXPIRATION_TIME);
    }

    private function getSymmetricConfig(): Configuration
    {
        $config = Configuration::forSymmetricSigner(
            new
            Sha256(),
            InMemory::plainText($this->applicationConfig->getTokenPrivateKey())
        );

        $config->setValidationConstraints(
            new ValidAt(SystemClock::fromUTC()),
            new SignedWith($config->signer(), $config->verificationKey()),
        );

        return $config;
    }
}
