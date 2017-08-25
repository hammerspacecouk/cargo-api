<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="users",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *     indexes={@ORM\Index(name="user_email", columns={"email"})})
 * )})
 */
class User extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $email;

    /** @ORM\Column(type="integer") */
    public $rotationSteps;

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
