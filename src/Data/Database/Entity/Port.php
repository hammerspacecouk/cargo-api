<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PortRepository")
 * @ORM\Table(
 *     name="ports",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )})
 */
class Port extends AbstractEntity
{
    /** @ORM\Column(type="string", unique=true, length=191) */
    public $name;

    /** @ORM\Column(type="boolean") */
    public $isSafeHaven = false;

    /** @ORM\Column(type="boolean") */
    public $isAHome = false;

    /** @ORM\Column(type="boolean") */
    public $isDestination = false;

    /** @ORM\Column(type="boolean") */
    public $isOpen = true;

    /** @ORM\Column(type="json") */
    public $coordinates = ['v' => '', 'b' => []];

    public function __construct(
        string $name,
        bool $isSafeHaven,
        bool $isAHome,
        bool $isDestination,
        bool $isOpen
    ) {
        parent::__construct();
        $this->name = $name;
        $this->isSafeHaven = $isSafeHaven;
        $this->isDestination = $isDestination;
        $this->isOpen = $isOpen;
        $this->isAHome = $isAHome;
    }
}
