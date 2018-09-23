<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use function App\Functions\Classes\shortHash;
use DateTimeImmutable;
use ParagonIE\Paseto\JsonToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractToken
{
    public const EXPIRY = 'PT1H';

    protected $token;
    protected $tokenString;
    private $id;
    private $expiry;

    public static function getSubject(): string
    {
        return shortHash(static::class, 8);
    }

    public function __construct(JsonToken $token, $tokenString)
    {
        $this->validateTokenType($token);
        $this->token = $token;
        $this->tokenString = $tokenString;
        $this->id = Uuid::fromString($token->getJti());
        $this->expiry = DateTimeImmutable::createFromMutable($token->getExpiration());
    }

    public function getOriginalToken(): JsonToken
    {
        return $this->token;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getExpiry(): DateTimeImmutable
    {
        return $this->expiry;
    }

    public function __toString(): string
    {
        return (string)\str_replace('v2.local.', '', $this->tokenString);
    }

    protected static function create(array $claims, $id = null): array
    {
        $tokenArgs = [
            $claims,
            static::getSubject(),
            static::EXPIRY,
        ];
        if ($id) {
            $tokenArgs[] = $id;
        }
        return $tokenArgs;
    }

    private function validateTokenType(JsonToken $token): void
    {
        if ($token->getSubject() !== static::getSubject()) {
            throw new InvalidTokenException('Token did not match expected type');
        }
    }
}
