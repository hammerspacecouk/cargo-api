<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Crate;
use App\Domain\Entity\CrateLocation;
use App\Domain\Entity\Null\NullEntity;

class CrateMapper extends Mapper
{
    public function getCrate(array $item): Crate
    {
        return new Crate(
            $item['id'],
            $item['contents'],
            $item['isDestroyed'],
            $this->getLocation($item)
        );
    }

    private function getLocation(?array $item): ?CrateLocation
    {
        if (array_key_exists('location', $item)) {
            $location = $item['location'];
            if (isset($location['port'])) {
                return $this->mapperFactory->createPortMapper()->getPort($location['port']);
            }
            if (isset($location['ship'])) {
                return $this->mapperFactory->createShipMapper()->getShip($location['ship']);
            }
            return new NullEntity();
        }
        return null;
    }
}
