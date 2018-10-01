<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\CrateRepository")
 * @ORM\Table(
 *     name="crates",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Crate extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $contents;

    /** @ORM\Column(type="integer") */
    public $value = 0;

    /** @ORM\Column(type="datetime_microsecond", nullable=true) */
    public $valueCalculationDate;

    /** @ORM\Column(type="integer") */
    public $valueChangeRate = 0;

    /** @ORM\Column(type="boolean") */
    public $isDestroyed = false;

    public function __construct(
        string $contents
    ) {
        parent::__construct();
        $this->contents = $contents;
    }
}
