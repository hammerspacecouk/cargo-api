<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use App\Data\ID;
use Doctrine\ORM\Mapping as ORM;

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

    /** @ORM\Column(type="datetime", nullable=false) */
    public $createdAt;

    /** @ORM\Column(type="datetime", nullable=false) */
    public $updatedAt;

    public function __construct()
    {
        $this->id = ID::makeNewID(static::class);
        $this->uuid = (string)$this->id;
    }
}
