<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use DateTimeImmutable;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractToken
{
    private const TYPE = null;
    private const KEY_TOKEN_TYPE = 'tt';

    protected $token;
    private $id;

    public function __construct(Token $token)
    {
        $this->validateTokenType($token);
        $this->token = $token;
        $this->id = Uuid::fromString($token->getClaim('jti'));
        $this->expiry = DateTimeImmutable::createFromFormat('U', (string) $token->getClaim('exp'));
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

    private function validateTokenType(Token $token): void
    {
        if (!$token->hasClaim(self::KEY_TOKEN_TYPE) ||
            $token->getClaim(self::KEY_TOKEN_TYPE) !== static::TYPE
        ) {
            throw new InvalidTokenException('Token did not match expected type');
        }
    }

    public static function createClaims(array $data): array {
        $data[self::KEY_TOKEN_TYPE] = static::TYPE;
        return $data;
    }
}