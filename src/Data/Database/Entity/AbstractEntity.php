<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use App\ApplicationTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

abstract class AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid_binary")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $uuid;

    /** @ORM\Column(type="datetime", nullable=false) */
    public $createdAt;

    /** @ORM\Column(type="datetime", nullable=false) */
    public $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->uuid = (string) $this->id;
    }

    /**
     * Set createdAt
     * @ORM\PrePersist
     */
    public function onCreate(): void
    {
        $this->createdAt = ApplicationTime::getTime();
        $this->updatedAt = ApplicationTime::getTime();
    }

    /**
     * Set updatedAt
     * @ORM\PreUpdate
     */
    public function onUpdate(): void
    {
        $this->updatedAt = ApplicationTime::getTime();
    }
}
