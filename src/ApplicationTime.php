<?php
declare(strict_types = 1);
namespace App;

use DateTimeImmutable;

/**
 * A Singleton class to help us ensure that our concept of "now" remains
 * consistent across multiple calls. This also allows for easy spoofing of when
 * "now" is.
 */
class ApplicationTime
{
    /** @var DateTimeImmutable  */
    private static $appTime = null;

    public static function getTime()
    {
        if (null === static::$appTime) {
            static::setTime();
        }

        return static::$appTime;
    }

    public static function setTime(int $appTime = null)
    {
        static::$appTime = DateTimeImmutable::createFromFormat('U', $appTime ?? (string) time());
    }

    /**
     * Blanks out any pre-set value of the time, so that a new value is
     * returned the next time we call this. Useful when testing.
     */
    public static function blank()
    {
        static::$appTime = null;
    }
}
