<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

use App\Domain\Entity\Null\NullEntity;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\ValueObject\ShipClass;

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
        if (array_key_exists('location', $item)) {
            $location = $item['location'];
            if ($location['port']) {
                return $this->mapperFactory->createPortMapper()->getPort($location['port']);
            }
            return new NullEntity();
        }
        return null;
    }
}
