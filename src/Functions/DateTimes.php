<?php
declare(strict_types=1);

namespace App\Functions\Classes;

use DateTime;
use DateTimeInterface;

/**
 * Needed until PHP 7.3
 * @param DateTimeInterface $dateTime
 * @return DateTime
 */
function toMutableDateTime(DateTimeInterface $dateTime): DateTime
{
    return (new DateTime())->setTimestamp($dateTime->getTimestamp());
}
