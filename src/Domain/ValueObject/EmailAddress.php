<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidEmailAddressException;

/**
 * An e-mail address stored in here is guaranteed to be valid, so it doesn't have to be checked again and again
 */
class EmailAddress implements \JsonSerializable
{
    private $emailAddress;

    public function __construct(string $emailAddress)
    {
        $this->emailAddress = $this->validate($emailAddress);
    }

    public function jsonSerialize(): string
    {
        return $this->emailAddress;
    }

    public function __toString(): string
    {
        return $this->emailAddress;
    }

    private function validate(string $emailAddress): string
    {
        $emailAddress = filter_var(trim($emailAddress), FILTER_VALIDATE_EMAIL);
        if (!$emailAddress) {
            throw new InvalidEmailAddressException('Invalid e-mail address provided');
        }
        return $emailAddress;
    }
}
