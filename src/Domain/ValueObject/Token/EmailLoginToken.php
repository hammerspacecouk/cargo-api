<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;

class EmailLoginToken extends AbstractToken
{
    public const TYPE = 'el';

    public const KEY_EMAIL_ADDRESS = 'ea';

    public static function makeClaims(
        string $emailAddress
    ): array {
        return parent::createClaims([
            self::KEY_EMAIL_ADDRESS => $emailAddress,
        ]);
    }

    public function getEmailAddress(): string
    {
        if ($this->token->hasClaim(self::KEY_EMAIL_ADDRESS)) {
            return $this->token->getClaim(self::KEY_EMAIL_ADDRESS);
        }
        throw new InvalidTokenException('No Email found');
    }
}
