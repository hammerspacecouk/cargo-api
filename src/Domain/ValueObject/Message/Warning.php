<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Message;

class Warning extends Message
{
    public const WEIGHTING = 20;
    protected const TYPE = 'warning';
}
