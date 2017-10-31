<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UserRepository")
 * @ORM\Table(
 *     name="users",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(name="user_email", columns={"email"})})
 * )})
 */
class User extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $email;

    /** @ORM\Column(type="boolean") */
    public $emailBlocked = false;

    /** @ORM\Column(type="integer") */
    public $rotationSteps;

    /** @ORM\Column(type="integer") */
    public $score = 0;

    /** @ORM\Column(type="integer") */
    public $scoreRate = 0;

    /** @ORM\Column(type="datetime", nullable=true) */
    public $scoreCalculationTime;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     */
    public $homePort;

    public function __construct(
        UuidInterface $id,
        string $email,
        int $rotationSteps
    ) {
        parent::__construct($id);
        $this->email = $email;
        $this->rotationSteps = $rotationSteps;
    }
}
