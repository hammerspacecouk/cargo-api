<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\AuthenticationTokenRepository")
 * @ORM\Table(
 *     name="authentication_tokens",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="auth_token_expiry", columns={"expiry"}),
 *      @ORM\Index(name="auth_token_last_used", columns={"last_used"}),
 *     }
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

    public function __construct(
        DateTimeImmutable $originalCreationTime,
        DateTimeImmutable $lastUsed,
        DateTimeImmutable $expiry,
        string $digest,
        User $user
    ) {
        parent::__construct();
        $this->originalCreationTime = $originalCreationTime;
        $this->lastUsed = $lastUsed;
        $this->expiry = $expiry;
        $this->digest = $digest;
        $this->user = $user;
    }
}
