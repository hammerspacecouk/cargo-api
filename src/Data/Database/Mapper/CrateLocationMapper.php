<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\CrateLocation;

class CrateLocationMapper extends Mapper
{
    public function getCrateLocation(array $item): CrateLocation
    {
        $crate = null;
        if (isset($item['crate'])) {
            $crate = $this->mapperFactory->createCrateMapper()->getCrate($item['crate']);
        }

        // split into CrateInPort and CrateOnShip if it becomes necessary
        return new CrateLocation(
            $item['id'],
            $item['isCurrent'],
            $crate,
        );
    }
}
