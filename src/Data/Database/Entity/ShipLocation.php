<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipLocationRepository")
 * @ORM\Table(
 *     name="ship_locations",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(name="ship_location_entry_time", columns={"entry_time"})})
 * )})
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

    /** @ORM\Column(type="boolean") */
    public $reverseDirection = false;

    /** @ORM\Column(type="datetime", nullable=false) */
    public $entryTime;

    /** @ORM\Column(type="datetime", nullable=true) */
    public $exitTime;

    public function __construct(
        UuidInterface $id,
        Ship $ship,
        ?Port $port,
        ?Channel $channel,
        DateTimeImmutable $entryTime
    ) {
        parent::__construct($id);
        $this->ship = $ship;
        $this->port = $port;
        $this->channel = $channel;
        $this->entryTime = $entryTime;
    }
}
