<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PlayerStandingRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="player_standings",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 */
class PlayerStanding extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="integer", unique=true) */
    public $orderNumber;

    /** @ORM\Column(type="integer", unique=true) */
    public $threshold;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $orderNumber,
        int $threshold
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->orderNumber = $orderNumber;
        $this->threshold = $threshold;
    }
}
