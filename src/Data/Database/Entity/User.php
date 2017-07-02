<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="users",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}
 * )})
 */
class User extends AbstractEntity
{
    /** @ORM\Column(type="string") */
    public $email;

    public function __construct(
        UuidInterface $id,
        string $email
    ) {
        parent::__construct($id);
        $this->email = $email;
    }
}
