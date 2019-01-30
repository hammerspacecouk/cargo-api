<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\HintRepository")
 * @ORM\Table(
 *      name="hints",
 *      options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Hint extends AbstractEntity
{
    /** @ORM\Column(type="text") */
    public $text;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     */
    public $minimumRank;

    public function __construct(
        string $text,
        PlayerRank $minimumRank
    ) {
        parent::__construct();
        $this->text = $text;
        $this->minimumRank = $minimumRank;
    }
}
