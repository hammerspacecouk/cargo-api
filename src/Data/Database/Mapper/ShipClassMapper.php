<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Data\Database\Mapper\Traits\MinimumRankTrait;
use App\Domain\Entity\ShipClass;

class ShipClassMapper extends Mapper
{
    use MinimumRankTrait;

    public function getShipClass(array $item): ShipClass
    {
        $domainEntity = new ShipClass(
            $item['id'],
            $item['name'],
            $item['description'],
            (int)$item['capacity'],
            (int)$item['strength'],
            (int)$item['purchaseCost'],
            $item['speedMultiplier'],
            $item['svg'],
            $this->getMinimumRank($item),
        );
        return $domainEntity;
    }
}
