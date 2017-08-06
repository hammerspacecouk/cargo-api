<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ports",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="name_idx", columns={"name"})},
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 */
class Port extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $name;

    /** @ORM\Column(type="boolean") */
    public $isSafeHaven = false;

    public function __construct(
        UuidInterface $id,
        string $name
    ) {
        parent::__construct($id);
        $this->name = $name;
    }
}
