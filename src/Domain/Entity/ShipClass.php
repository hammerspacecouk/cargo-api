<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class ShipClass extends Entity implements \JsonSerializable
{
    private string $name;
    private int $capacity;
    private float $speedMultiplier;
    private int $strength;
    private ?PlayerRank $minimumRank;
    private int $purchaseCost;
    private string $description;
    private string $imageSvg;
    private int $displayStrength;
    private int $displaySpeed;
    private int $displayCapacity;
    private bool $autoNavigate;

    public function __construct(
        UuidInterface $id,
        string $name,
        string $description,
        int $capacity,
        int $strength,
        int $purchaseCost,
        bool $autoNavigate,
        float $speedMultiplier,
        string $imageSvg,
        int $displayStrength,
        int $displaySpeed,
        int $displayCapacity,
        ?PlayerRank $minimumRank
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->capacity = $capacity;
        $this->speedMultiplier = $speedMultiplier;
        $this->strength = $strength;
        $this->minimumRank = $minimumRank;
        $this->purchaseCost = $purchaseCost;
        $this->description = $description;
        $this->imageSvg = $imageSvg;
        $this->displayStrength = $displayStrength;
        $this->displaySpeed = $displaySpeed;
        $this->displayCapacity = $displayCapacity;
        $this->autoNavigate = $autoNavigate;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'type' => 'ShipClass',
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'capacity' => $this->getCapacity(),
            'isProbe' => $this->isProbe(),
            'strength' => $this->strength,
            'image' => $this->getImagePath(),
            'stats' => [
                'max' => 10,
                'strength' => $this->displayStrength,
                'speed' => $this->displaySpeed,
                'capacity' => $this->displayCapacity,
            ],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImage(): string
    {
        return $this->imageSvg;
    }

    public function getImageHash(): string
    {
        // use a hash of the svg data as a cache buster.
        return \sha1($this->imageSvg);
    }

    public function getImagePath(): string
    {
        return '/ship-class/' . $this->id->toString() . '-' . $this->getImageHash() . '.svg';
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function isProbe(): bool
    {
        return $this->autoNavigate && $this->capacity === 0;
    }

    public function getSpeedMultiplier(): float
    {
        return $this->speedMultiplier;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function getMinimumRank(): PlayerRank
    {
        if ($this->minimumRank === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship class minimum rank, but it was not fetched'
            );
        }
        return $this->minimumRank;
    }

    public function getPurchaseCost(): int
    {
        return $this->purchaseCost;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDisplayStrength(): int
    {
        return $this->displayStrength;
    }

    public function getDisplaySpeed(): int
    {
        return $this->displaySpeed;
    }

    public function getDisplayCapacity(): int
    {
        return $this->displayCapacity;
    }
}
