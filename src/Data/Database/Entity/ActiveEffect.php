<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ActiveEffectRepository")
 * @ORM\Table(
 *     name="active_effects",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="active_effects_expiry", columns={"expiry"}),
 *      @ORM\Index(name="active_effects_for_user", columns={"applies_to_user_id","expiry"}),
 *      @ORM\Index(name="active_effects_for_port", columns={"applies_to_port_id","expiry"}),
 *      @ORM\Index(name="active_effects_for_ship", columns={"applies_to_ship_id","expiry"}),
 *     }
 * )})
 */
class ActiveEffect extends AbstractEntity
{
    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public DateTimeImmutable $expiry;

    /** @ORM\Column(type="integer", nullable=true) */
    public ?int $remainingCount;

    /**
     * @ORM\ManyToOne(targetEntity="Effect")
     */
    public ?Effect $effect;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public User $triggeredBy;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     */
    public ?Port $appliesToPort = null;

    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public ?Ship $appliesToShip = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public ?User $appliesToUser = null;

    public function __construct(
        ?Effect $effect,
        ?int $remainingCount,
        DateTimeImmutable $expiry,
        User $triggeredBy
    ) {
        parent::__construct();
        $this->effect = $effect;
        $this->expiry = $expiry;
        $this->triggeredBy = $triggeredBy;
        $this->remainingCount = $remainingCount;
    }
}
