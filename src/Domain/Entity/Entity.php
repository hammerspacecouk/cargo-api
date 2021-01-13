<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

abstract class Entity
{
    public function __construct(
        protected UuidInterface $id
    ) {
    }

    public function equals(Entity $entity): bool
    {
        return $this->getId()->equals($entity->getId());
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
}
