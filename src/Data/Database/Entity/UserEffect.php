<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UserEffectRepository")
 * @ORM\Table(
 *     name="user_effects",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="user_effects_expiry", columns={"used_at"}),
 *      @ORM\Index(name="user_effects_for_user", columns={"user_id","used_at"}),
 *     }
 * )})
 */
class UserEffect extends AbstractEntity
{
    /** @ORM\Column(type="datetime_microsecond") */
    public DateTimeImmutable $collectedAt;

    /** @ORM\Column(type="datetime_microsecond", nullable=true) */
    public ?DateTimeImmutable $usedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity="Effect")
     */
    public Effect $effect;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public User $user;

    public function __construct(
        User $user,
        Effect $achievement,
        DateTimeImmutable $collectedAt
    ) {
        parent::__construct();
        $this->user = $user;
        $this->effect = $achievement;
        $this->collectedAt = $collectedAt;
    }
}
