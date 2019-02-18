<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class ShipClass extends Entity implements \JsonSerializable
{
    private $name;
    private $capacity;
    private $speedMultiplier;
    private $strength;
    private $minimumRank;
    private $purchaseCost;
    private $description;
    private $imageSvg;

    public function __construct(
        UuidInterface $id,
        string $name,
        string $description,
        int $capacity,
        int $strength,
        int $purchaseCost,
        float $speedMultiplier,
        string $imageSvg,
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
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'ShipClass',
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'capacity' => $this->getCapacity(),
            'strength' => $this->strength,
            'image' => $this->getImagePath(),
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
}
