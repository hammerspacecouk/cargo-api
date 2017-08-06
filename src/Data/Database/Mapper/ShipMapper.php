<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\ValueObject\ShipClass;
use App\Domain\ValueObject\Travelling;

class ShipMapper extends Mapper
{
    public function getShip(array $item): Ship
    {
        return new Ship(
            $item['id'],
            $item['name'],
            $this->getShipClass($item),
            $this->getLocation($item)
        );
    }

    private function getShipClass(?array $item): ?ShipClass
    {
        if (isset($item['shipClass'])) {
            return $this->mapperFactory->createShipClassMapper()->getShipClass($item['shipClass']);
        }
        return null;
    }

    private function getLocation(?array $item): ?ShipLocation
    {
        if (isset($item['location'])) {
            $location = $item['location'];
            if (isset($location['port'])) {
                return $this->mapperFactory->createPortMapper()->getPort($location['port']);
            }
            if (isset($location['channel'])) {
                return new Travelling();
            }
        }
        return null;
    }
}
