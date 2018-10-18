<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class ShipClass implements \JsonSerializable
{
    private $name;
    private $capacity;
    private $speedMultiplier;
    private $strength;

    public function __construct(
        string $name,
        int $capacity,
        int $strength,
        float $speedMultiplier
    ) {
        $this->name = $name;
        $this->capacity = $capacity;
        $this->speedMultiplier = $speedMultiplier;
        $this->strength = $strength;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'ShipClass',
            'name' => $this->getName(),
            'capacity' => $this->getCapacity(),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getSpeedMultiplier(): float
    {
        return $this->speedMultiplier;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }
}
