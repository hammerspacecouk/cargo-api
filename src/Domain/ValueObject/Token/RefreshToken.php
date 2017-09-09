<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;

class RefreshToken extends AbstractToken
{
    public const TYPE = 'rf';

    public const KEY_ACCESS_KEY = 'ak';

    public static function makeClaims(
        string $accessKey
    ): array {
        return parent::createClaims([self::KEY_ACCESS_KEY => $accessKey]);
    }

    public static function secureAccessKey(string $accessKey): string
    {
        return password_hash($accessKey, PASSWORD_DEFAULT);
    }

    public function validateAccessKey(string $digest): bool
    {
        if (password_verify($this->getAccessKey(), $digest)) {
            return true;
        }
        throw new InvalidTokenException('Invalid Access Key');
    }

    public function getAccessKey(): string
    {
        if ($this->token->hasClaim(self::KEY_ACCESS_KEY)) {
            return $this->token->getClaim(self::KEY_ACCESS_KEY);
        }
        throw new InvalidTokenException('No Access Key found');
    }
}
