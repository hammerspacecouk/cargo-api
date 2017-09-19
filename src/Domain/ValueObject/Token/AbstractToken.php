<?php declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use DateTimeImmutable;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractToken
{
    public const KEY_TOKEN_TYPE = 'tt';
    public const KEY_TOKEN_ID = 'jti';
    public const KEY_TOKEN_EXPIRY = 'exp';

    private const TYPE = null;

    protected $token;
    private $id;
    private $expiry;

    public function __construct(Token $token)
    {
        $this->validateTokenType($token);
        $this->token = $token;
        $this->id = Uuid::fromString($token->getClaim(self::KEY_TOKEN_ID));
        $this->expiry = DateTimeImmutable::createFromFormat('U', (string)$token->getClaim(self::KEY_TOKEN_EXPIRY));
    }

    private function validateTokenType(Token $token): void
    {
        if (!$token->hasClaim(self::KEY_TOKEN_TYPE) ||
            $token->getClaim(self::KEY_TOKEN_TYPE) !== static::TYPE
        ) {
            throw new InvalidTokenException('Token did not match expected type');
        }
    }

    public static function createClaims(array $data): array
    {
        $data[self::KEY_TOKEN_TYPE] = static::TYPE;
        return $data;
    }

    public function getOriginalToken(): Token
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
        return (string)$this->token;
    }
}
