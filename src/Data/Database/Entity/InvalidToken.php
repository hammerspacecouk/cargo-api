<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\InvalidTokenRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="invalid_tokens",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *     indexes={@ORM\Index(name="invalid_token_status", columns={"status"})})
 * )})
 */
class InvalidToken extends AbstractEntity
{
    public const STATUS_USED = 'USED';
    public const STATUS_RESERVED = 'RESERVED';

    /** @ORM\Column(type="datetime", nullable=true) */
    public $invalidUntil;

    /** @ORM\Column(type="string") */
    public $status;

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $invalidUntil,
        string $status
    ) {
        parent::__construct($id);
        $this->invalidUntil = $invalidUntil;
        $this->status = $status;
    }
}
