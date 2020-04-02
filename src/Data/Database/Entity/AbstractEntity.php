<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid_binary")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    public $uuid;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public $createdAt;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::uuid6();
        $this->uuid = $this->id->toString();
    }
}
