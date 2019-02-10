<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Effect\TravelEffect;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Ship;

class AlgorithmService extends AbstractService
{
    private const MINIMUM_JOURNEY_TIME_SECONDS = 10;

    public const MINIMUM_EARNINGS_DISTANCE = 1/100;

    public function getJourneyTime(
        int $distanceUnits,
        Ship $ship,
        PlayerRank $playerRank,
        ?TravelEffect $activeTravelEffect = null
    ): int {
        // base time is the longest without modifications (except really slow ships)
        $time = $this->applicationConfig->getDistanceMultiplier() * $distanceUnits;

        // ships may make it faster or slower (usually faster)
        $time *= $ship->getShipClass()->getSpeedMultiplier();

        // earlier classes are faster
        $time *= $playerRank->getSpeedMultiplier();

        if ($activeTravelEffect) {
            if ($activeTravelEffect->isInstant()) {
                $time = 0;
            } else {
                $time /= $activeTravelEffect->getSpeedDivisor();
            }
        }

        return self::MINIMUM_JOURNEY_TIME_SECONDS + (int)\round($time);
    }

    public function getTotalEarnings(int $totalCrateValue, int $distance, ?TravelEffect $activeTravelEffect): int
    {
        $value = $totalCrateValue * ($distance ?: self::MINIMUM_EARNINGS_DISTANCE);
        if ($activeTravelEffect) {
            $value *= $activeTravelEffect->getEarningsMultiplier();
        }
        return (int)\round($value);
    }
}
