<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\ValueObject\ShipClass;

class ShipClassMapper extends Mapper
{
    public function getShipClass(array $item): ShipClass
    {
        $domainEntity = new ShipClass(
            $item['name'],
            $item['capacity']
        );
        return $domainEntity;
    }
}
