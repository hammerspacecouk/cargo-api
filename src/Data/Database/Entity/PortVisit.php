<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortVisitRepository")
 * @ORM\Table(
 *     name="port_visits",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="port_visit_unique", columns={"player_id", "port_id"})
 *     }
 * )
 */
class PortVisit extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public User $player;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public Port $port;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public DateTimeInterface $firstVisited;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public DateTimeInterface $lastVisited;

    public function __construct(
        User $player,
        Port $port,
        DateTimeInterface $firstVisited
    ) {
        parent::__construct();
        $this->player = $player;
        $this->port = $port;
        $this->firstVisited = $firstVisited;
        // if you're making a new one 'first' and 'last' are the same
        $this->lastVisited = $firstVisited;
    }
}
