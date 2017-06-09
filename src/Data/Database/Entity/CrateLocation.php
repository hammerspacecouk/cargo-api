<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="crate_locations",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *     indexes={@ORM\Index(name="crate_location_created", columns={"created_at"})})
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\CrateLocationRepository")
 */
class CrateLocation extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Crate")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $crate;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $port;

    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $ship;

    public function __construct(
        UuidInterface $id,
        Crate $crate,
        ?Port $port,
        ?Ship $ship
    ) {
        parent::__construct($id);
        $this->crate = $crate;
        $this->port = $port;
        $this->ship = $ship;
    }
}
