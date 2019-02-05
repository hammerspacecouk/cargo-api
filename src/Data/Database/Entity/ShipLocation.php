<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipLocationRepository")
 * @ORM\Table(
 *     name="ship_locations",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="ship_location_entry_time", columns={"entry_time"}),
 *      @ORM\Index(name="ship_location_exit_time", columns={"exit_time"}),
 *      @ORM\Index(name="ship_location_current_exit", columns={"is_current", "exit_time"}),
 *      @ORM\Index(name="ship_location_current_ship", columns={"is_current", "ship_id"}),
 *     })
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

    /** @ORM\Column(type="integer", nullable=true) */
    public $scoreDelta;

    /** @ORM\Column(type="boolean") */
    public $reverseDirection = false;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public $entryTime;

    /** @ORM\Column(type="datetime_microsecond", nullable=true) */
    public $exitTime;

    public function __construct(
        Ship $ship,
        ?Port $port,
        ?Channel $channel,
        DateTimeImmutable $entryTime
    ) {
        parent::__construct();
        $this->ship = $ship;
        $this->port = $port;
        $this->channel = $channel;
        $this->entryTime = $entryTime;
    }

    public function getDestination(): Port
    {
        if (!$this->channel) {
            throw new DataNotFetchedException('Tried to getDestination on an object with no channel data');
        }

        if ($this->reverseDirection) {
            return $this->channel->fromPort;
        }
        return $this->channel->toPort;
    }

    public function getOrigin(): Port
    {
        if (!$this->channel) {
            throw new DataNotFetchedException('Tried to getDestination on an object with no channel data');
        }
        if ($this->reverseDirection) {
            return $this->channel->toPort;
        }
        return $this->channel->fromPort;
    }
}
