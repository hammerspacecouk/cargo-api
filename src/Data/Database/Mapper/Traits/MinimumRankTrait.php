<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper\Traits;

use App\Domain\Entity\PlayerRank;

trait MinimumRankTrait
{
    private function getMinimumRank(?array $item): ?PlayerRank
    {
        if (isset($item['minimumRank'])) {
            return $this->mapperFactory->createPlayerRankMapper()->getPlayerRank($item['minimumRank']);
        }
        return null;
    }
}
