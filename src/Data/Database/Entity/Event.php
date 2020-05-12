<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
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
    public DateTimeImmutable $time;

    /** @ORM\Column(type="string") */
    public string $action;

    /** @ORM\Column(type="string", nullable=true) */
    public ?string $value = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    public ?User $actioningPlayer = null;

    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    public ?Ship $actioningShip = null;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     * @ORM\JoinColumn(nullable=true)
     */
    public ?PlayerRank $subjectRank = null;

    /**
     * @ORM\ManyToOne(targetEntity="Ship")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    public ?Ship $subjectShip = null;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(nullable=true)
     */
    public ?Port $subjectPort = null;

    /**
     * @ORM\ManyToOne(targetEntity="Crate")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    public ?Crate $subjectCrate = null;

    /**
     * @ORM\ManyToOne(targetEntity="Effect")
     * @ORM\JoinColumn(nullable=true)
     */
    public ?Effect $subjectEffect = null;

    public function __construct(
        DateTimeImmutable $time,
        string $action
    ) {
        parent::__construct();
        $this->time = $time;
        $this->action = $action;
    }
}
