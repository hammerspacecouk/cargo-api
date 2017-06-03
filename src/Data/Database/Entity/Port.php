<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ports",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="name_idx", columns={"name"})},
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortRepository")
 */
class Port extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $name;

    public function __construct(
        string $name
    ) {
        parent::__construct();
        $this->name = $name;
    }
}
