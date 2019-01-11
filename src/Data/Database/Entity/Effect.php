<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\EffectRepository")
 * @ORM\Table(
 *     name="effects",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Effect extends AbstractEntity
{
    /** @ORM\Column(type="enum_effects") */
    public $type;

    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="text") */
    public $description;

    /** @ORM\Column(type="text") */
    public $svg;

    /** @ORM\Column(type="integer") */
    public $purchaseCost;

    /** @ORM\Column(type="integer", nullable=true) */
    public $duration;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     */
    public $minimumRank;

    public function __construct(
        string $type,
        string $name,
        string $description,
        string $svg,
        int $purchaseCost = 0
    ) {
        parent::__construct();
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->svg = $svg;
        $this->purchaseCost = $purchaseCost;
    }
}
