<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Costs
{
    // costs of things to do
    public const ACTION_REQUEST_SHIP_NAME = 500;

    // health
    public const SMALL_HEALTH = 500;
    public const SMALL_HEALTH_INCREASE = 100;
    public const LARGE_HEALTH = 1500;
    public const LARGE_HEALTH_INCREASE = 500;
}
