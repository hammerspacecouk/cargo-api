<?php
declare(strict_types=1);

namespace App\Service;

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


    public function processOldestExpired(): bool
    {
        $location = $this->entityManager->getShipLocationRepo()->getOldestExpired(Query::HYDRATE_OBJECT);
        if ($location) {
            $this->moveShipFromChannelToPort($location);
            return true;
        }
        return false;
    }

    private function moveShipFromChannelToPort(DbShipLocation $currentLocation): void
    {
        $ship = $currentLocation->ship;
        $destinationPort = $currentLocation->getDestination();

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

            // todo - add this port to the list of visited ports for this user
            // todo - move all the crates to the port
            // calculate the user's new rank and cache it

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
