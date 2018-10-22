<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\CrateTypeRepository")
 * @ORM\Table(
 *     name="crate_types",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class CrateType extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $contents;

    /** @ORM\Column(type="integer") */
    public $abundance;

    /** @ORM\Column(type="integer") */
    public $value;

    /** @ORM\Column(type="boolean") */
    public $isGoal = false;

    public function __construct(
        string $contents,
        int $abundance,
        int $value,
        bool $isGoal
    ) {
        parent::__construct();
        $this->contents = $contents;
        $this->abundance = $abundance;
        $this->isGoal = $isGoal;
        $this->value = $value;
    }
}
