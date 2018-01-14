<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;

class CsrfToken extends AbstractToken
{
    public const TYPE = 'csrf';

    public const KEY_CONTEXT_KEY = 'csrf';

    public static function makeClaims(
        string $contextKey
    ): array {
        return parent::createClaims([self::KEY_CONTEXT_KEY => $contextKey]);
    }

    public function getContextKey(): string
    {
        if ($this->token->hasClaim(self::KEY_CONTEXT_KEY)) {
            return $this->token->getClaim(self::KEY_CONTEXT_KEY);
        }
        throw new InvalidTokenException('No Context Key found');
    }
}
