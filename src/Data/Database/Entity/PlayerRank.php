<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PlayerRankRepository")
 * @ORM\Table(
 *     name="player_ranks",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(name="player_ranks_threshold", columns={"threshold"})})
 * )
 */
class PlayerRank extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="integer", unique=true) */
    public $threshold;

    public function __construct(
        string $name,
        int $threshold
    ) {
        parent::__construct();
        $this->name = $name;
        $this->threshold = $threshold;
    }
}
