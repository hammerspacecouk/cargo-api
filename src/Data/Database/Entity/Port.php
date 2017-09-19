<?php declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortRepository")
 * @ORM\Table(
 *     name="ports",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Port extends AbstractEntity
{
    /** @ORM\Column(type="string", unique=true) */
    public $name;

    /** @ORM\Column(type="boolean") */
    public $isSafeHaven = false;

    /** @ORM\Column(type="boolean") */
    public $isOpen = true;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafeHaven,
        bool $isOpen
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafeHaven = $isSafeHaven;
        $this->isOpen = $isOpen;
    }
}
