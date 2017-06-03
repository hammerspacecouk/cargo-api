<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="crate_locations",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
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

    /** @ORM\Column(type="boolean") */
    public $isCurrent = true;
}
