<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject;

class ShipClass implements \JsonSerializable
{
    private $name;
    private $capacity;

    public function __construct(
        string $name,
        int $capacity
    ) {
        $this->name = $name;
        $this->capacity = $capacity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'ShipClass',
            'name' => $this->getName(),
            'capacity' => $this->getCapacity(),
        ];
    }
}
