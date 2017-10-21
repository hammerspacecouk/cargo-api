<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ClusterRepository")
 * @ORM\Table(
 *     name="clusters",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Cluster extends AbstractEntity
{
    /** @ORM\Column(type="string", unique=true) */
    public $name;

    public function __construct(
        UuidInterface $id,
        string $name
    ) {
        parent::__construct($id);
        $this->name = $name;
    }
}
