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
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class PortVisit extends AbstractEntity
{
    /**
     * @ORM\Column(nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     */
    public $player;

    /**
     * @ORM\Column(nullable=false)
     * @ORM\ManyToOne(targetEntity="Port")
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
