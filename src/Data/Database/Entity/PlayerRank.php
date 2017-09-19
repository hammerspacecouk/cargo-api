<?php declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PlayerRankRepository")
 * @ORM\Table(
 *     name="player_ranks",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class PlayerRank extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="integer", unique=true) */
    public $orderNumber;

    /** @ORM\Column(type="float", unique=true) */
    public $threshold;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $orderNumber,
        float $threshold
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->orderNumber = $orderNumber;
        $this->threshold = $threshold;
    }
}
