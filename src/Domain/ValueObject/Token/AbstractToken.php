<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\TokenId;
use DateTimeImmutable;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\RegisteredClaims;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Strings\shortHash;

abstract class AbstractToken
{
    public const EXPIRY = 'PT1H';

    protected Plain $token;
    protected string $tokenString;
    private TokenId $id;
    private DateTimeImmutable $expiry;

    public static function getSubject(): string
    {
        return shortHash(static::class, 8);
    }

    public function __construct(Plain $token)
    {
        $this->validateTokenType($token);
        $this->token = $token;
        $this->id = TokenId::fromString($token->claims()->get(RegisteredClaims::ID));
        /** @var DateTimeImmutable $expiry */
        $expiry = $token->claims()->get(RegisteredClaims::EXPIRATION_TIME);
        $this->expiry = $expiry;
    }

    public function getOriginalToken(): Plain
    {
        return $this->token;
    }

    public function getId(): TokenId
    {
        return $this->id;
    }

    public function getExpiry(): DateTimeImmutable
    {
        return $this->expiry;
    }

    public function __toString(): string
    {
        return $this->token->toString();
    }

    protected static function create(array $claims, TokenId $id = null): array
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

    protected function getAnOptionalId(string $key): ?UuidInterface
    {
        if ($this->token->claims()->has($key) && !empty($this->token->claims()->get($key))) {
            return Uuid::fromString($this->token->claims()->get($key));
        }
        return null;
    }

    private function validateTokenType(Plain $token): void
    {
        if (!$token->isRelatedTo(static::getSubject())) {
            throw new InvalidTokenException('Token did not match expected type');
        }
    }
}
