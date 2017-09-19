<?php declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class User extends Entity implements \JsonSerializable
{
    private $email;
    private $rotationSteps;

    public function __construct(
        UuidInterface $id,
        string $email,
        int $rotationSteps
    ) {
        parent::__construct($id);
        $this->email = $email;
        $this->rotationSteps = $rotationSteps;
    }

    public function jsonSerialize()
    {
        // the User object must not be exposed
        return null;
    }

    public function getRotationSteps()
    {
        return $this->rotationSteps;
    }
}
