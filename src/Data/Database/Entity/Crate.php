<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\CrateRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="crates",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 */
class Crate extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $contents;

    /** @ORM\Column(type="float") */
    public $value = 0;

    /** @ORM\Column(type="date", nullable=true) */
    public $valueCalculationDate;

    /** @ORM\Column(type="float") */
    public $valueChangeRate = 0;

    /** @ORM\Column(type="boolean") */
    public $isDestroyed = false;

    public function __construct(
        UuidInterface $id,
        string $contents
    ) {
        parent::__construct($id);
        $this->contents = $contents;
    }
}
