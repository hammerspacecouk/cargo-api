<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\EventRepository")
 * @ORM\Table(
 *     name="events",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="event_time", columns={"time"}),
 *     })
 * )})
 */
class Event extends AbstractEntity
{
    /** @ORM\Column(type="datetime_microsecond") */
    public $time;

    /** @ORM\Column(type="string") */
    public $action;

    /** @ORM\Column(type="string", nullable=true) */
    public $value;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $actioningPlayer;

    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $actioningShip;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $subjectRank;

    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $subjectShip;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $subjectPort;

    /**
     * @ORM\ManyToOne(targetEntity="Crate")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $subjectCrate;

    public function __construct(
        \DateTimeImmutable $time,
        string $action
    ) {
        parent::__construct();
        $this->time = $time;
        $this->action = $action;
    }
}
