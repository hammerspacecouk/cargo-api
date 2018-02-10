<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Message;

class Error extends Message
{
    public const WEIGHTING = 100;
    protected const TYPE = 'error';
}
