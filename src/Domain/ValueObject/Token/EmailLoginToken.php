<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\ValueObject\EmailAddress;

class EmailLoginToken extends AbstractToken
{
    private const KEY_EMAIL_ADDRESS = 'ea';

    public static function make(
        EmailAddress $emailAddress
    ): array {
        return parent::create([
            self::KEY_EMAIL_ADDRESS => (string)$emailAddress,
        ]);
    }

    public function getEmailAddress(): EmailAddress
    {
        return new EmailAddress($this->token->get(self::KEY_EMAIL_ADDRESS));
    }
}
