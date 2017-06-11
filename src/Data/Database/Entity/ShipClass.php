<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use App\Data\Database\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ship_classes",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipClassRepository")
 */
class ShipClass extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $name;

    /** @ORM\Column(type="integer", unique=true) */
    public $orderNumber;

    /** @ORM\Column(type="integer") */
    public $capacity;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $orderNumber,
        int $capacity
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->orderNumber = $orderNumber;
        $this->capacity = $capacity;
    }
}
