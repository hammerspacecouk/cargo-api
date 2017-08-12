<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ships",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 */
class Ship extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $owner;

    /**
     * @ORM\ManyToOne(targetEntity="ShipClass")
     * @ORM\JoinColumn()
     */
    public $shipClass;

    public function __construct(
        UuidInterface $id,
        string $name,
        ShipClass $shipClass,
        User $owner
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->shipClass = $shipClass;
        $this->owner = $owner;
    }
}
