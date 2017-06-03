<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="crates",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\CrateRepository")
 */
class Crate extends AbstractEntity
{
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DESTROYED = 'DESTROYED';

    /** @ORM\Column(type="string") */
    public $contents;

    /** @ORM\Column(type="float") */
    public $value = 0;

    /** @ORM\Column(type="date", nullable=true) */
    public $valueCalculationDate;

    /** @ORM\Column(type="float") */
    public $valueChangeRate = 0;

    /** @ORM\Column(type="string") */
    public $status = self::STATUS_INACTIVE;

    // island location
    // ship location

    public function __construct(
        string $contents
    ) {
        parent::__construct();
        $this->contents = $contents;
    }
}
