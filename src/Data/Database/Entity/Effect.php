<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use App\Data\Database\Entity\Traits\OrderNumberTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\EffectRepository")
 * @ORM\Table(
 *     name="effects",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="effect_order", columns={"order_number"}),
 *      @ORM\Index(name="effect_display_group", columns={"display_group"})
 *     })
 * )})
 */
class Effect extends AbstractEntity
{
    use OrderNumberTrait;

    /** @ORM\Column(type="enum_effects") */
    public string $type;

    /** @ORM\Column(type="enum_effect_display_group") */
    public string $displayGroup;

    /** @ORM\Column(type="text") */
    public string $name;

    /** @ORM\Column(type="text") */
    public string $description;

    /** @ORM\Column(type="integer") */
    public int $oddsOfWinning;

    /** @ORM\Column(type="text") */
    public string $svg;

    /**
     * @var mixed
     * @ORM\Column(type="json", nullable=true)
     */
    public $value;

    /** @ORM\Column(type="integer", nullable=true) */
    public ?int $purchaseCost = null;

    /** @ORM\Column(type="integer", nullable=true) */
    public ?int $duration = null;

    /** @ORM\Column(type="integer", nullable=true) */
    public ?int $count = null;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     */
    public ?PlayerRank $minimumRank = null;

    public function __construct(
        string $type,
        string $name,
        string $displayGroup,
        int $orderNumber,
        string $description,
        int $oddsOfWinning,
        string $svg,
        PlayerRank $minimumRank
    ) {
        parent::__construct();
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->svg = $svg;
        $this->orderNumber = $orderNumber;
        $this->oddsOfWinning = $oddsOfWinning;
        $this->minimumRank = $minimumRank;
        $this->displayGroup = $displayGroup;
    }
}
