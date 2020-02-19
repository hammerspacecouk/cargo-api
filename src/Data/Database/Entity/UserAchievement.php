<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UserAchievementRepository")
 * @ORM\Table(
 *     name="user_achievements",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="userach_unique", columns={"user_id", "achievement_id"})
 *     }
 * )})
 */
class UserAchievement extends AbstractEntity
{
    /** @ORM\Column(type="datetime_microsecond") */
    public $collectedAt;

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
        User $user,
        Achievement $achievement,
        DateTimeImmutable $collectedAt
    ) {
        parent::__construct();
        $this->user = $user;
        $this->achievement = $achievement;
        $this->collectedAt = $collectedAt;
    }
}
