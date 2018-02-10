<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Message;

class Info extends Message
{
    public const WEIGHTING = 10;
    protected const TYPE = 'info';
}
