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

    private const AUTO_MOVE_TIME = 'PT1H';

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
            Query::HYDRATE_OBJECT,
        );
        $total = count($locations);

        $this->logger->info('Processing ' . $total . ' arrivals in this batch');
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
            $ownerId,
        );
        $makeNewCrate = null;
        // if this was their first travel from the home (visits = 1) we're going to make a new crate
        if (!$alreadyVisited && $portVisitRepo->countForPlayerId($ownerId) === 1) {
            $makeNewCrate = true;
        }

        // reverse the delta from this journey originally
        $delta = -$this->calculateDelta(
            $ship->id,
            $currentLocation->channel->distance,
            $currentLocation->entryTime,
            $currentLocation->exitTime,
        );
        $crateLocations = $this->entityManager->getCrateLocationRepo()->findCurrentForShipID(
            $ship->id,
            Query::HYDRATE_OBJECT,
        );

        $this->entityManager->transactional(function () use (
            $currentLocation,
            $ship,
            $destinationPort,
            $alreadyVisited,
            $owner,
            $crateLocations,
            $delta,
            $makeNewCrate,
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
                $this->entityManager->getCrateRepo()->removeReservation($crateLocation->crate);
            }

            if ($makeNewCrate) {
                $crate = $this->entityManager->getCrateRepo()->newRandomCrate();
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crate,
                    $destinationPort
                );
            }

            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($owner, $delta);
        });
    }

    public function autoMoveShips(
        DateTimeImmutable $before,
        int $limit
    ): int {
        // find ships of capacity 0 that have been sitting in a port for a while

        // for each of them, find all the possible directions they can use

        // of the possible directions, find which ones the ship is allowed to travel
        // of the remaining directions, find one which the player has NOT been to before
        // if not found, choose the one the player hasn't been to most recently
    }

    // todo - move location based methods from ShipsService into here - maybe
}
