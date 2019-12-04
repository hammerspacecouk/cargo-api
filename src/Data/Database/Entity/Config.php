<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ConfigRepository")
 * @ORM\Table(
 *      name="config",
 *      options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Config extends AbstractEntity
{
    /** @ORM\Column(type="json") */
    public $value;

    public function __construct(
        array $value
    ) {
        parent::__construct();
        $this->value = $value;
    }
}
