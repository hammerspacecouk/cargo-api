<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

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

    /**
     * @ORM\ManyToOne(targetEntity="Channel")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $channel;

    /** @ORM\Column(type="boolean") */
    public $isCurrent = true;

    public function __construct(
        UuidInterface $id,
        ?Ship $ship,
        ?Port $port,
        Channel $channel
    ) {
        parent::__construct($id);
        $this->ship = $ship;
        $this->port = $port;
        $this->channel = $channel;
    }
}
