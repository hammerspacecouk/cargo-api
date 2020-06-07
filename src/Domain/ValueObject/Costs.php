<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Costs
{
    // costs of things to do
    public const ACTION_REQUEST_SHIP_NAME = 5000;

    // health
    public const SMALL_HEALTH = 5000;
    public const SMALL_HEALTH_INCREASE = 100;
    public const LARGE_HEALTH = 10000;
    public const LARGE_HEALTH_INCREASE = 500;
}
