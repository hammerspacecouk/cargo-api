<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipClassRepository")
 * @ORM\Table(
 *     name="ship_classes",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class ShipClass extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="integer", unique=true) */
    public $orderNumber;

    /** @ORM\Column(type="integer") */
    public $capacity;

    /** @ORM\Column(type="boolean") */
    public $isStarterShip;

    /** @ORM\Column(type="integer") */
    public $purchaseCost;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     */
    public $minimumRank;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $orderNumber,
        int $capacity,
        bool $isStarterShip,
        int $purchaseCost,
        ?PlayerRank $minimumRank
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->orderNumber = $orderNumber;
        $this->capacity = $capacity;
        $this->isStarterShip = $isStarterShip;
        $this->purchaseCost = $purchaseCost;
        $this->minimumRank = $minimumRank;
    }
}
