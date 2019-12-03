<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Domain\Entity\Port;
use App\Domain\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;

class ShipLocationsService extends AbstractService
{
    private const AUTO_MOVE_TIME = 'PT1H';

    public function findLatest(
        int $limit,
        int $page = 1
    ): array {
        $locations = $this->entityManager->getShipLocationRepo()->getLatest($limit, $this->getOffset($limit, $page));
        return $this->mapMany($locations);
    }

    public function processOldestExpired(
        DateTimeImmutable $since,
        int $limit
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

    public function getStagnantProbes(
        DateTimeImmutable $before,
        int $limit
    ): array {
        $results = $this->entityManager->getShipLocationRepo()
            ->getInPortOfCapacity(
                $before->sub(new DateInterval(self::AUTO_MOVE_TIME)),
                0,
                $limit
            );
        return $this->mapMany($results);
    }

    public function getLatestVisitTimeForPort(User $user, Port $port): ?DateTimeImmutable
    {
        $visit = $this->entityManager->getPortVisitRepo()->getForPortAndUser(
            $port->getId(),
            $user->getId(),
        );
        if ($visit) {
            return $visit['lastVisited'];
        }
        return null;
    }

    private function moveShipFromChannelToPort(DbShipLocation $currentLocation): void
    {
        $ship = $currentLocation->ship;
        $destinationPort = $currentLocation->getDestination();

        $usersRepo = $this->entityManager->getUserRepo();
        $portVisitRepo = $this->entityManager->getPortVisitRepo();

        $ownerId = $ship->owner->id;
        $owner = $usersRepo->getByID($ownerId, Query::HYDRATE_OBJECT);
        $portVisit = $portVisitRepo->getForPortAndUser(
            $destinationPort->id,
            $ownerId,
            Query::HYDRATE_OBJECT,
        );
        $makeNewCrate = null;
        // if this was their first travel from the home (visits = 1) we're going to make a new crate
        if (!$portVisit && $portVisitRepo->countForPlayerId($ownerId) === 1) {
            $makeNewCrate = true;
        }

        // reverse the delta from this journey originally
        $delta = -$currentLocation->scoreDelta;

        $crateLocations = $this->entityManager->getCrateLocationRepo()->findCurrentForShipID(
            $ship->id,
            Query::HYDRATE_OBJECT,
        );

        $this->entityManager->transactional(function () use (
            $currentLocation,
            $ship,
            $destinationPort,
            $portVisit,
            $owner,
            $crateLocations,
            $delta,
            $makeNewCrate
        ) {
            // remove the old ship location
            $currentLocation->isCurrent = false;
            $this->entityManager->persist($currentLocation);

            $this->entityManager->getShipLocationRepo()->makeInPort($ship, $destinationPort);

            // add this port to the list of visited ports for this user
            $this->entityManager->getPortVisitRepo()->recordVisit($portVisit, $owner, $destinationPort);

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

    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createShipLocationMapper();

        return \array_map(static function ($result) use ($mapper) {
            return $mapper->getShipLocation($result);
        }, $results);
    }
}
