<?php
declare(strict_types=1);

namespace App\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;

class DateTimeFactory
{
    private const FORMAT = 'Y-m-d\TH:i:s.uP'; // todo - make private

    private static ?DateTimeImmutable $instance = null;

    public static function now(): DateTimeImmutable
    {
        if (!static::$instance) {
            return static::reset();
        }
        return static::$instance;
    }

    public static function reset(): DateTimeImmutable
    {
        static::$instance = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return static::$instance;
    }

    public static function set(DateTimeImmutable $newDate): void
    {
        static::$instance = $newDate;
    }

    public static function toJson(?DateTimeImmutable $dateTime): ?string
    {
        if ($dateTime) {
            return $dateTime->format(self::FORMAT);
        }
        return null;
    }
}
