<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\AuthenticationTokenRepository")
 * @ORM\Table(
 *     name="authentication_tokens",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )
 */
class AuthenticationToken extends AbstractEntity
{
    /** @ORM\Column(type="datetime", nullable=false) */
    public $lastUpdate;

    /** @ORM\Column(type="datetime", nullable=true) */
    public $expiry;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    public $user;

    /** @ORM\Column(type="text", nullable=true) */
    public $digest;

    /** @ORM\Column(type="text", nullable=true) */
    public $description;

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $lastUpdate,
        ?DateTimeImmutable $expiry,
        ?User $user = null,
        ?string $digest = null,
        ?string $description = null
    ) {
        parent::__construct($id);
        $this->lastUpdate = $lastUpdate;
        $this->expiry = $expiry;
        $this->user = $user;
        $this->digest = $digest;
        $this->description = $description;
    }
}
