<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\RankAchievementRepository")
 * @ORM\Table(
 *     name="rank_achievements",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="rankach_unique", columns={"rank_id", "achievement_id"})
 *     }
 * )})
 */
class RankAchievement extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     */
    public $rank;

    /**
     * @ORM\ManyToOne(targetEntity="Achievement")
     */
    public $achievement;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $user;

    public function __construct(
        PlayerRank $rank,
        Achievement $achievement
    ) {
        parent::__construct();
        $this->rank = $rank;
        $this->achievement = $achievement;
    }
}
