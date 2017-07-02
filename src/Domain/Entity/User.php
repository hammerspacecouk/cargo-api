<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use Ramsey\Uuid\Uuid;

class User extends Entity implements \JsonSerializable
{
    private $email;

    public function __construct(
        Uuid $id,
        string $email
    ) {
        parent::__construct($id);
        $this->email = $email;
    }

    public function jsonSerialize()
    {
        // the User object must not be exposed
        return null;
    }
}
