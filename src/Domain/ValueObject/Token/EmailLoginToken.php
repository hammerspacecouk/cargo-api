<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;

class EmailLoginToken extends AbstractToken
{
    public const TYPE = 'el';

    public const KEY_EMAIL_ADDRESS = 'ea';
    public const KEY_RETURN_ADDRESS = 'ra';

    public static function makeClaims(
        string $emailAddress,
        string $returnAddress = null
    ): array {
        return parent::createClaims([
            self::KEY_EMAIL_ADDRESS => $emailAddress,
            self::KEY_RETURN_ADDRESS => $returnAddress,
        ]);
    }

    public function getEmailAddress(): string
    {
        if ($this->token->hasClaim(self::KEY_EMAIL_ADDRESS)) {
            return $this->token->getClaim(self::KEY_EMAIL_ADDRESS);
        }
        throw new InvalidTokenException('No Email found');
    }

    public function getReturnAddress(): ?string
    {
        if ($this->token->hasClaim(self::KEY_RETURN_ADDRESS)) {
            return $this->token->getClaim(self::KEY_RETURN_ADDRESS);
        }
        throw new InvalidTokenException('No Return address found');
    }
}
