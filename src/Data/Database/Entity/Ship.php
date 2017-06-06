<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use App\Data\Database\Entity\ShipClass;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ships",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipRepository")
 */
class Ship extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="ShipClass")
     * @ORM\JoinColumn()
     */
    public $shipClass;

    public function __construct(
        string $name,
        ShipClass $shipClass
    ) {
        parent::__construct();
        $this->name = $name;
        $this->shipClass = $shipClass;
    }
}
