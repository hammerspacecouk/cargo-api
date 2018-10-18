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
            (int)$item['capacity'],
            (int)$item['strength'],
            $item['speedMultiplier']
        );
        return $domainEntity;
    }
}
