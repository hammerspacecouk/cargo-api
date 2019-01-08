<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipRepository")
 * @ORM\Table(
 *     name="ships",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *          @ORM\Index(name="ships_strength", columns={"strength"}),
 *     }
 * )})
 */
class Ship extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="integer") */
    public $strength;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $owner;

    /**
     * @ORM\ManyToOne(targetEntity="ShipClass")
     * @ORM\JoinColumn()
     */
    public $shipClass;

    /** @ORM\Column(type="json_array") */
    public $activeEffects;

    public function __construct(
        string $name,
        ShipClass $shipClass,
        User $owner
    ) {
        parent::__construct();
        $this->name = $name;
        $this->shipClass = $shipClass;
        $this->strength = $shipClass->strength; // starts with the full strength of the class
        $this->owner = $owner;
    }
}
