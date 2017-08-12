<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use Lcobucci\JWT\Token;

abstract class AbstractToken
{
    private const TYPE = null;
    private const KEY_TOKEN_TYPE = 'tt';

    protected $token;

    public function __construct(Token $token)
    {
        $this->validateTokenType($token);
        $this->token = $token;
    }

    public function getOriginalToken(): Token
    {
        return $this->token;
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