<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\AchievementRepository")
 * @ORM\Table(
 *     name="achievements",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Achievement extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $name;

    /** @ORM\Column(type="text") */
    public $description;

    /** @ORM\Column(type="integer", nullable=true) */
    public $displayOrder;

    /** @ORM\Column(type="text") */
    public $svg;

    /** @ORM\Column(type="boolean") */
    public $isHidden;

    public function __construct(
        string $name,
        string $description,
        int $displayOrder,
        bool $isHidden,
        string $svg
    ) {
        parent::__construct();
        $this->name = $name;
        $this->displayOrder = $displayOrder;
        $this->svg = $svg;
        $this->description = $description;
        $this->isHidden = $isHidden;
    }
}
