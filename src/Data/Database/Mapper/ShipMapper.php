<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\ShipClass;

class ShipMapper extends Mapper
{
    public function getShip(array $item): Ship
    {
        return new Ship(
            $item['id'],
            $item['name'],
            $this->getOwner($item),
            $this->getShipClass($item),
            $this->getLocation($item)
        );
    }

    private function getOwner(?array $item): ?User
    {
        if (isset($item['owner'])) {
            return $this->mapperFactory->createUserMapper()->getUser($item['owner']);
        }
        return null;
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
            return $this->mapperFactory->createShipLocationMapper()->getShipLocation($item['location']);
        }
        return null;
    }
}
