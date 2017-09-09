<?php
declare(strict_types=1);

namespace App\Service;

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

    // todo - move location based methods from ShipsService into here
}
