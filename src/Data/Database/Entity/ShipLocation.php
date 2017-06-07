<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ship_locations",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *     indexes={@ORM\Index(name="ship_location_created", columns={"created_at"})})
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipLocationRepository")
 */
class ShipLocation extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $ship;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $port;

    public function __construct(
        Ship $ship,
        Port $port
    ) {
        parent::__construct();
        $this->ship = $ship;
        $this->port = $port;
    }
}
