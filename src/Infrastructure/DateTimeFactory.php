<?php
declare(strict_types=1);

namespace App\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;

class DateTimeFactory
{
    public const FULL = 'Y-m-d\TH:i:s.uP';

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
