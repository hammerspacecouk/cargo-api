<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Message;

class Ok extends Message
{
    public const WEIGHTING = 0;
    protected const TYPE = 'Ok';
}
