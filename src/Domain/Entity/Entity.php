<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use Ramsey\Uuid\Uuid;

abstract class Entity
{
    protected $id;

    public function __construct(
        UUID $id
    ) {
        $this->id = $id;
    }

    public function getId(): UUID
    {
        return $this->id;
    }
}
