<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

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
    public UuidInterface $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    public string $uuid;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public ?DateTimeImmutable $createdAt = null;

    /** @ORM\Column(type="datetime_microsecond", nullable=false) */
    public ?DateTimeImmutable $updatedAt = null;

    /** @ORM\Column(type="datetime_microsecond", nullable=true) */
    public ?DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->id = Uuid::uuid6();
        $this->uuid = $this->id->toString();
    }
}
