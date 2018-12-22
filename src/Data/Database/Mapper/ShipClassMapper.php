<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\ShipClass;

class ShipClassMapper extends Mapper
{
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
            $this->getMinimumRank($item)
        );
        return $domainEntity;
    }

    private function getMinimumRank(?array $item): ?PlayerRank
    {
        if (isset($item['minimumRank'])) {
            return $this->mapperFactory->createPlayerRankMapper()->getPlayerRank($item['minimumRank']);
        }
        return null;
    }
}
