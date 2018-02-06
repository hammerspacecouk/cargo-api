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
    /** @ORM\Column(type="datetime") */
    public $originalCreationTime;

    /** @ORM\Column(type="datetime") */
    public $lastUsed;

    /** @ORM\Column(type="datetime") */
    public $expiry;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    public $user;

    /** @ORM\Column(type="text") */
    public $digest;

    /** @ORM\Column(type="text") */
    public $description;

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $originalCreationTime,
        DateTimeImmutable $lastUsed,
        DateTimeImmutable $expiry,
        string $digest,
        string $description,
        User $user
    ) {
        parent::__construct($id);
        $this->originalCreationTime = $originalCreationTime;
        $this->lastUsed = $lastUsed;
        $this->expiry = $expiry;
        $this->digest = $digest;
        $this->description = $description;
        $this->user = $user;
    }
}
