<?php
declare(strict_types=1);

namespace App\Functions\Dates;

use DateInterval;

function intervalToSeconds(DateInterval $interval): int
{
    return (int)(($interval->days * 3600 * 24) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s);
}
