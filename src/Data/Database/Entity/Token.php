<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\TokenRepository")
 * @ORM\Table(
 *     name="tokens",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(name="invalid_token_type", columns={"type"})})
 * )})
 */
class Token extends AbstractEntity
{
    public const TYPE_REFRESH = 'REFRESH';
    public const TYPE_USED = 'USED';
    public const TYPE_RESERVED = 'RESERVED';
    public const TYPE_INVALIDATED = 'INVALIDATED';

    public const INVALID_TYPES = [
        self::TYPE_USED,
        self::TYPE_RESERVED,
        self::TYPE_INVALIDATED,
    ];

    /** @ORM\Column(type="datetime", nullable=false) */
    public $lastUpdate;

    /** @ORM\Column(type="datetime", nullable=true) */
    public $expiry;

    /** @ORM\Column(type="string") */
    public $type;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    public $user;

    /** @ORM\Column(type="text", nullable=true) */
    public $digest;

    /** @ORM\Column(type="text", nullable=true) */
    public $description;

    public function __construct(
        UuidInterface $id,
        string $type,
        DateTimeImmutable $lastUpdate,
        ?DateTimeImmutable $expiry,
        ?User $user = null,
        ?string $digest = null,
        ?string $description = null
    ) {
        parent::__construct($id);
        $this->type = $type;
        $this->lastUpdate = $lastUpdate;
        $this->expiry = $expiry;
        $this->user = $user;
        $this->digest = $digest;
        $this->description = $description;
    }
}
