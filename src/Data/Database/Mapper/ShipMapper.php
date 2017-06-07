<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;

class ShipMapper extends Mapper
{
    public function getShip(array $item): Ship
    {
        return new Ship(
            $item['id'],
            $item['name'],
            $this->getShipClass($item)
        );
    }

    private function getShipClass(?array $item)
    {
        if ($item['shipClass']) {
            return $this->mapperFactory->createShipClassMapper()->getShipClass($item['shipClass']);
        }
        return null;
    }

    private function getLocation(?array $item): ?ShipLocation
    {
        $location = $item['location'] ?? null;
        if ($location['port']) {
            return $this->mapperFactory->createPortMapper()->getPort($location['port']);
        }
        return null;
    }
}
