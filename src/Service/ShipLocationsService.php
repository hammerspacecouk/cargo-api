<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\PortVisit;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\ID;
use Doctrine\ORM\Query;

class ShipLocationsService extends AbstractService
{
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


    public function processOldestExpired($limit): void
    {
        $locations = $this->entityManager->getShipLocationRepo()->getOldestExpired($limit, Query::HYDRATE_OBJECT);
        $this->logger->info('Processing ' . count($locations) . ' arrivals in this batch');
        if (!empty($locations)) {
            foreach ($locations as $location) {
                $this->moveShipFromChannelToPort($location);
            }
        }
    }

    private function moveShipFromChannelToPort(DbShipLocation $currentLocation): void
    {
        $ship = $currentLocation->ship;
        $destinationPort = $currentLocation->getDestination();

        $usersRepo = $this->entityManager->getUserRepo();
        $ownerId = $ship->owner->id;
        $owner = $usersRepo->getByID($ownerId, Query::HYDRATE_OBJECT);
        $alreadyVisited = $this->entityManager->getPortVisitRepo()->existsForPortAndUser(
            $destinationPort->id,
            $ownerId
        );

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // remove the old ship location
            $currentLocation->isCurrent = false;
            $currentLocation->exitTime = $this->currentTime;
            $this->entityManager->persist($currentLocation);

            // make a new ship location
            $newLocation = new DbShipLocation(
                ID::makeNewID(DbShipLocation::class),
                $ship,
                $destinationPort,
                null,
                $this->currentTime
            );
            $this->entityManager->persist($newLocation);

            // add this port to the list of visited ports for this user
            if (!$alreadyVisited) {
                $portVisit = new PortVisit(
                    ID::makeNewID(PortVisit::class),
                    $owner,
                    $destinationPort,
                    $this->currentTime
                );
                $this->entityManager->persist($portVisit);
            }

            // todo - move all the crates to the port
            // todo - calculate the user's new rank and cache it

            // update the users score - todo - calculate how much the rate delta should be
            $this->entityManager->getUserRepo()->updateScore($owner, -1);

            $this->entityManager->flush();
            $this->logger->info('Committing all changes');
            $this->entityManager->getConnection()->commit();

            $this->logger->notice(sprintf(
                '[ARRIVAL] Ship: %s, Port: %s',
                (string)$ship->uuid,
                (string)$newLocation->uuid
            ));
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Failed to process arrival');
            throw $e;
        }
    }

    // todo - move location based methods from ShipsService into here
}
