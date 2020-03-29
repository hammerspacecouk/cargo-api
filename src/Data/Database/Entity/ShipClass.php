<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use App\Data\Database\Entity\Traits\OrderNumberTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipClassRepository")
 * @ORM\Table(
 *     name="ship_classes",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="ship_class_order", columns={"order_number"})
 *     })
 * )})
 */
class ShipClass extends AbstractEntity
{
    use OrderNumberTrait;

    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="text") */
    public $description;

    /** @ORM\Column(type="integer") */
    public $strength;

    /** @ORM\Column(type="boolean") */
    public $autoNavigate = false;

    /** @ORM\Column(type="integer") */
    public $capacity;

    /** @ORM\Column(type="float") */
    public $speedMultiplier = 1;

    /** @ORM\Column(type="boolean") */
    public $isStarterShip;

    /** @ORM\Column(type="boolean") */
    public $isDefenceShip = false;

    /** @ORM\Column(type="integer") */
    public $purchaseCost;

    /** @ORM\Column(type="text") */
    public $svg;

    /** @ORM\Column(type="integer") */
    public $displayCapacity;

    /** @ORM\Column(type="integer") */
    public $displaySpeed;

    /** @ORM\Column(type="integer") */
    public $displayStrength;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     */
    public $minimumRank;

    public function __construct(
        string $name,
        string $description,
        int $strength,
        bool $autoNavigate,
        int $orderNumber,
        int $capacity,
        float $speedMultiplier,
        bool $isStarterShip,
        bool $isDefenceShip,
        int $purchaseCost,
        string $svg,
        int $displayCapacity,
        int $displaySpeed,
        int $displayStrength,
        PlayerRank $minimumRank
    ) {
        parent::__construct();
        $this->name = $name;
        $this->orderNumber = $orderNumber;
        $this->capacity = $capacity;
        $this->isStarterShip = $isStarterShip;
        $this->isDefenceShip = $isDefenceShip;
        $this->purchaseCost = $purchaseCost;
        $this->minimumRank = $minimumRank;
        $this->speedMultiplier = $speedMultiplier;
        $this->description = $description;
        $this->strength = $strength;
        $this->autoNavigate = $autoNavigate;
        $this->svg = $svg;
        $this->displayCapacity = $displayCapacity;
        $this->displaySpeed = $displaySpeed;
        $this->displayStrength = $displayStrength;
    }
}
