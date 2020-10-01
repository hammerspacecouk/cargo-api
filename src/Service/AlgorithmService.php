<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Effect\TravelEffect;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\TacticalEffect;

class AlgorithmService extends AbstractService
{
    private const MINIMUM_JOURNEY_TIME_SECONDS = 10;

    public const MINIMUM_EARNINGS_DISTANCE = 1/100;

    /**
     * @param int $distanceUnits
     * @param Ship $ship
     * @param User $user
     * @param TacticalEffect[] $activeTravelEffects
     * @return int
     */
    public function getJourneyTime(
        int $distanceUnits,
        Ship $ship,
        User $user,
        $activeTravelEffects = []
    ): int {
        // base time is the longest without modifications (except really slow ships)
        $time = $this->applicationConfig->getDistanceMultiplier() * $distanceUnits;

        // ships may make it faster or slower (usually faster)
        $time *= $ship->getShipClass()->getSpeedMultiplier();

        // earlier classes are faster
        $time *= $user->getRank()->getSpeedMultiplier();

        // market multiplier (lowest = 0, highest = 100)
        $time *= $user->getMarket()->getDiscoveryMultiplier();

        foreach ($activeTravelEffects as $activeTravelEffect) {
            $effect = $activeTravelEffect->getEffect();
            if (!$effect instanceof TravelEffect) {
                continue;
            }

            if ($effect->isInstant()) {
                $time = 0;
            } else {
                $time /= $effect->getSpeedDivisor();
            }
        }

        if ($ship->hasPlague()) {
            $time *= 2;
        }

        return self::MINIMUM_JOURNEY_TIME_SECONDS + (int)\round($time);
    }

    /**
     * @param int $totalCrateValue
     * @param int $distance
     * @param TacticalEffect[] $activeTravelEffects
     * @return int
     */
    public function getTotalEarnings(
        int $totalCrateValue,
        int $distance,
        array $activeTravelEffects = []
    ): int {
        $value = $totalCrateValue * ($distance ?: self::MINIMUM_EARNINGS_DISTANCE);
        foreach ($activeTravelEffects as $activeTravelEffect) {
            $effect = $activeTravelEffect->getEffect();
            if (!$effect instanceof TravelEffect) {
                continue;
            }
            $value *= $effect->getEarningsMultiplier();
        }
        return (int)\round($value);
    }
}
