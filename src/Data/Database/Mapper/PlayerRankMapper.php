<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\PlayerRank;

class PlayerRankMapper extends Mapper
{
    public function getPlayerRank(array $item): PlayerRank
    {
        return new PlayerRank(
            $item['id'],
            $item['name'],
            $item['threshold'],
            $item['marketCredits'],
            !empty($item['description']) ? $item['description'] : null,
        );
    }
}
