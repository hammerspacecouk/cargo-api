<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortVisitRepository")
 * @ORM\Table(
 *     name="port_visits",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="port_visit_unique", columns={"player_id", "port_id"})
 *     }
 * )
 * todo - support a INSERT IF NOT EXISTS query
 */
class PortVisit extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $player;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $port;

    /** @ORM\Column(type="datetime", nullable=false) */
    public $firstVisited;

    public function __construct(
        UuidInterface $id,
        User $player,
        Port $port,
        DateTimeInterface $firstVisited
    ) {
        parent::__construct($id);
        $this->player = $player;
        $this->port = $port;
        $this->firstVisited = $firstVisited;
    }
}
