<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Service\AlgorithmService;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

trait DeltaTrait
{
    private function calculateDelta(
        UuidInterface $shipId,
        int $distance,
        DateTimeImmutable $entryTime,
        DateTimeImmutable $exitTime
    ): int {
        // get crates currently on this ship to calculate delta
        $crateLocations = $this->entityManager->getCrateLocationRepo()->findCurrentForShipID($shipId);
        $crateMapper = $this->mapperFactory->createCrateMapper();
        $totalCrateValue = \array_reduce($crateLocations, function (int $acc, array $crateLocation) use($crateMapper) {
            return $acc + $crateMapper->getCrate($crateLocation['crate'])
                    ->getValuePerLightYear($this->applicationConfig->getDistanceMultiplier());
        }, 0);

        // todo - this duplicates AlgorithmService - move it
        $totalAmountToEarn = (int)\round($totalCrateValue * ($distance ?: AlgorithmService::MINIMUM_EARNINGS_DISTANCE));
        $totalSeconds = $exitTime->getTimestamp() - $entryTime->getTimestamp();

        // delta is the amount per second
        return (int)\floor($totalAmountToEarn / $totalSeconds);
    }
}
