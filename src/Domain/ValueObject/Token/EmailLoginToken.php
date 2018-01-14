<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\EmailAddress;

class EmailLoginToken extends AbstractToken
{
    public const TYPE = 'el';

    public const KEY_EMAIL_ADDRESS = 'ea';

    public static function makeClaims(
        EmailAddress $emailAddress
    ): array {
        return parent::createClaims([
            self::KEY_EMAIL_ADDRESS => (string) $emailAddress,
        ]);
    }

    public function getEmailAddress(): EmailAddress
    {
        if ($this->token->hasClaim(self::KEY_EMAIL_ADDRESS)) {
            return new EmailAddress($this->token->getClaim(self::KEY_EMAIL_ADDRESS));
        }
        throw new InvalidTokenException('No Email found');
    }
}
