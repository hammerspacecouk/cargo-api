<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

use App\Domain\Entity\Ship;

class ShipMapper extends Mapper
{
    public function getShip(array $item): Ship
    {
        $shipClass = null;
        if (isset($item['shipClass'])) {
            $shipClass = $this->mapperFactory->createShipClassMapper()->getShipClass($item['shipClass']);
        }

        $domainEntity = new Ship(
            $item['id'],
            $item['name'],
            $shipClass
        );
        return $domainEntity;
    }
}
