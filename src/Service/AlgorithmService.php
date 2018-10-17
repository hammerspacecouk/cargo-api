<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Ship;
use App\Domain\ValueObject\PlayerRankStatus;

class AlgorithmService extends AbstractService
{
    private const MINIMUM_JOURNEY_TIME_SECONDS = 20;

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
}
