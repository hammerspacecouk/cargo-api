<?php
declare(strict_types=1);

namespace App\Data;

use App\Domain\Entity\Ship;
use App\Domain\ValueObject\PlayerRankStatus;
use App\Infrastructure\ApplicationConfig;

class Algorithm
{
    private const MINIMUM_JOURNEY_TIME_SECONDS = 36;

    public const MINIMUM_EARNINGS_DISTANCE = 1/100;

    private $applicationConfig;

    public function __construct(
        ApplicationConfig $applicationConfig
    ) {
        $this->applicationConfig = $applicationConfig;
    }

    public function getJourneyTime(
        int $distanceUnits,
        Ship $ship,
        PlayerRankStatus $rankStatus
    ): int {
        // base time is the longest without modifications (except really slow ships)
        $time = $this->applicationConfig->getDistanceMultiplier() * $distanceUnits;

        // ships may make it faster or slower (usually faster)
        $time *= $ship->getShipClass()->getSpeedMultiplier();

        // earlier classes are faster
        $applicableClass = $rankStatus->getNextRank() ?? $rankStatus->getCurrentRank();
        $time *= $applicableClass->getSpeedMultiplier();

        return self::MINIMUM_JOURNEY_TIME_SECONDS + (int)\round($time);
    }

    public function getTotalEarnings(int $totalCrateValue, int $distance): int
    {
        return (int)\round($totalCrateValue * ($distance ?: self::MINIMUM_EARNINGS_DISTANCE));
    }
}
