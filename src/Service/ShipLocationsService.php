<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use function App\Functions\Dates\intervalToSeconds;
use function array_map;

class ShipLocationsService extends AbstractService
{
    private const AUTO_MOVE_TIME = 'PT1H';

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

    public function getRecentForShip(Ship $ship): array
    {
        $locations = $this->entityManager->getShipLocationRepo()->getRecentForShipID(
            $ship->getId(),
            6
        );
        return $this->mapMany($locations);
    }

    /**
     * @param DateTimeImmutable $before
     * @param int $limit
     * @return ShipLocation[]
     */
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
        /** @var DbUser $owner */
        $owner = $usersRepo->getByID($ownerId, Query::HYDRATE_OBJECT);
        $portVisit = $portVisitRepo->getForPortAndUser(
            $destinationPort->id,
            $ownerId,
            Query::HYDRATE_OBJECT,
        );
        $isFirstJourney = null;
        // if this was their first travel from the home (visits = 1) we're going to make a new crate
        if (!$portVisit && $portVisitRepo->countForPlayerId($ownerId) === 1) {
            $isFirstJourney = true;
        }

        // reverse the delta from this journey originally
        $delta = (int)-$currentLocation->scoreDelta;

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
            $isFirstJourney
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

                if ($crateLocation->crate->isGoal && $destinationPort->isDestination) {
                    // winner
                    $this->entityManager->getUserAchievementRepo()->recordWin($owner->id);
                    $this->entityManager->getUserRepo()->recordWinner($owner);
                }
            }

            if ($isFirstJourney) {
                $crate = $this->entityManager->getCrateRepo()->newRandomCrate();
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crate,
                    $destinationPort
                );
                $this->entityManager->getUserAchievementRepo()->recordFirstTravel($owner->id);
            }

            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($owner, $delta);

            $timeInChannel = $currentLocation->entryTime->diff($currentLocation->entryTime);
            if (intervalToSeconds($timeInChannel) >= 60 * 60 * 24) {
                $this->entityManager->getUserAchievementRepo()->recordLongTravel($owner->id);
            }

            if (!$destinationPort->isSafeHaven) {
                $this->entityManager->getUserAchievementRepo()->recordArrivalToUnsafeTerritory($owner->id);
            }
        });

        // as a safety check if some race condition happened, confirm the user delta
        $expectedDelta = $this->entityManager->getShipLocationRepo()->sumDeltaForUserId($ownerId);
        $owner->scoreRate = $expectedDelta;
        $this->entityManager->persist($owner);
        $this->entityManager->flush();
    }

    /**
     * @param array[] $results
     * @return ShipLocation[]
     */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createShipLocationMapper();

        return array_map(static function ($result) use ($mapper) {
            return $mapper->getShipLocation($result);
        }, $results);
    }
}
