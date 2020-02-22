<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Achievement;

class AchievementMapper extends Mapper
{
    public function getAchievement(array $item): Achievement
    {
        return new Achievement(
            $item['id'],
            $item['name'],
            $item['description'],
            $item['svg'],
            $item['collectedAt'] ?? null
        );
    }
}
