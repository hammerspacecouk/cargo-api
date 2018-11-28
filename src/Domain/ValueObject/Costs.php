<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Costs
{
    // costs of things to do
    public const ACTION_REQUEST_SHIP_NAME = 500;

    // delta values of state changes
    public const DELTA_SHIP_DEPARTURE = 1;
    public const DELTA_SHIP_ARRIVAL = -self::DELTA_SHIP_DEPARTURE;

    // health
    public const SMALL_HEALTH = 500;
    public const SMALL_HEALTH_INCREASE = 30;
    public const LARGE_HEALTH = 1500;
    public const LARGE_HEALTH_INCREASE = 100;
}
