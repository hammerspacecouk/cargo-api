<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Service\Ships\DeltaTrait;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;

class ShipLocationsService extends AbstractService
{
    use DeltaTrait;

    public function findLatest(
        int $limit,
        int $page = 1
    ): array {
        $locations = $this->entityManager->getShipLocationRepo()->getLatest($limit, $this->getOffset($limit, $page));

        $mapper = $this->mapperFactory->createShipLocationMapper();

        return array_map(function ($result) use ($mapper) {
            return $mapper->getShipLocation($result);
        }, $locations);
    }

    public function processOldestExpired(
        DateTimeImmutable $since,
        $limit
    ): int {
        $locations = $this->entityManager->getShipLocationRepo()->getOldestExpired(
            $since,
            $limit,
            Query::HYDRATE_OBJECT
        );
        $total = count($locations);

        $this->logger->info('Processing ' . count($locations) . ' arrivals in this batch');
        if (!empty($locations)) {
            foreach ($locations as $location) {
                $this->moveShipFromChannelToPort($location);
            }
        }
        return $total;
    }

    public function removeInactive(
        DateTimeImmutable $now
    ): int {
        $daysToConsiderInactive = 14;
        $before = $now->sub(new DateInterval('P' . $daysToConsiderInactive . 'D'));
        return $this->entityManager->getShipLocationRepo()->removeInactiveBefore($before);
    }

    private function moveShipFromChannelToPort(DbShipLocation $currentLocation): void
    {
        $ship = $currentLocation->ship;
        $destinationPort = $currentLocation->getDestination();

        $usersRepo = $this->entityManager->getUserRepo();
        $portVisitRepo = $this->entityManager->getPortVisitRepo();

        $ownerId = $ship->owner->id;
        $owner = $usersRepo->getByID($ownerId, Query::HYDRATE_OBJECT);
        $alreadyVisited = $portVisitRepo->existsForPortAndUser(
            $destinationPort->id,
            $ownerId
        );

        // reverse the delta from this journey originally
        $delta = -$this->calculateDelta(
            $ship->id,
            $currentLocation->channel->distance,
            $currentLocation->entryTime,
            $currentLocation->exitTime
        );
        $crateLocations = $this->entityManager->getCrateLocationRepo()->findCurrentForShipID(
            $ship->id,
            Query::HYDRATE_OBJECT
        );

        $this->entityManager->transactional(function () use (
            $currentLocation,
            $ship,
            $destinationPort,
            $alreadyVisited,
            $owner,
            $crateLocations,
            $delta
        ) {
            // remove the old ship location
            $currentLocation->isCurrent = false;
            $this->entityManager->persist($currentLocation);

            $this->entityManager->getShipLocationRepo()->makeInPort($ship, $destinationPort);

            // add this port to the list of visited ports for this user
            if (!$alreadyVisited) {
                $this->entityManager->getPortVisitRepo()->recordVisit($owner, $destinationPort);
            }

            // move all the crates to the port
            foreach ($crateLocations as $crateLocation) {
                /** @var CrateLocation $crateLocation */
                $crateLocation->isCurrent = false;
                $this->entityManager->getCrateLocationRepo()->exitLocation($crateLocation->crate);
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crateLocation->crate,
                    $destinationPort
                );
            }

            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($owner, $delta);
        });
    }

    // todo - move location based methods from ShipsService into here - maybe
}
