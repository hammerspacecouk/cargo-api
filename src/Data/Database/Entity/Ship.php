<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="ships",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ShipRepository")
 */
class Ship extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $name;

    /** @ORM\Column(type="int") */
    public $class = 0;

    public function __construct(
        string $name
    ) {
        parent::__construct();
        $this->name = $name;
    }
}
