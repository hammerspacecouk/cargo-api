<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortRepository")
 * @ORM\Table(
 *     name="ports",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Port extends AbstractEntity
{
    /** @ORM\Column(type="string", unique=true, length=191) */
    public $name;

    /** @ORM\Column(type="boolean") */
    public $isSafeHaven = false;

    /** @ORM\Column(type="boolean") */
    public $isDestination = false;

    /** @ORM\Column(type="boolean") */
    public $isOpen = true;

    /**
     * @ORM\ManyToOne(targetEntity="Cluster")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public $cluster;

    public function __construct(
        string $name,
        ?Cluster $cluster,
        bool $isSafeHaven,
        bool $isDestination,
        bool $isOpen
    ) {
        parent::__construct();
        $this->name = $name;
        $this->cluster = $cluster;
        $this->isSafeHaven = $isSafeHaven;
        $this->isDestination = $isDestination;
        $this->isOpen = $isOpen;
    }
}
