<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Domain\Entity\ShipClass;
use App\Service\ShipsService;
use Ramsey\Uuid\UuidInterface;

class ShipClassService extends ShipsService
{
    public function fetchById(UuidInterface $classId): ?ShipClass
    {
        $result = $this->entityManager->getShipClassRepo()->getByID($classId);
        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipClassMapper();
        return $mapper->getShipClass($result);
    }
}
