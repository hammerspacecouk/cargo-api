<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class Ship extends Entity implements \JsonSerializable
{
    private $name;
    private $shipClass;
    private $location;
    private $owner;
    private $strength;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $strength,
        ?User $owner,
        ?ShipClass $shipClass,
        ?ShipLocation $location = null
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->shipClass = $shipClass;
        $this->location = $location;
        $this->owner = $owner;
        $this->strength = $strength;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStrengthPercent(): int
    {
        return (int)(($this->strength / $this->getShipClass()->getStrength()) * 100);
    }

    public function isDestroyed(): bool
    {
        return $this->strength <= 0;
    }

    public function isFullStrength(): bool
    {
        return $this->getStrengthPercent() === 100;
    }

    public function isHealthy(): bool
    {
        return $this->getStrengthPercent() > 25;
    }

    public function meetsStrength(int $minimumStrength): bool
    {
        return $this->strength >= $minimumStrength;
    }

    public function getLocation(): ShipLocation
    {
        if ($this->location === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship location, but it was not fetched'
            );
        }
        return $this->location;
    }

    public function getOwner(): User
    {
        if ($this->owner === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship owner, but it was not fetched'
            );
        }
        return $this->owner;
    }

    public function getShipClass(): ShipClass
    {
        if ($this->shipClass === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship ShipClass, but it was not fetched'
            );
        }
        return $this->shipClass;
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'type' => 'Ship',
            'name' => $this->name,
            'isDestroyed' => $this->isDestroyed(),
        ];
        if ($this->owner) {
            $data['owner'] = $this->owner;
        }
        if ($this->shipClass) {
            $data['shipClass'] = $this->shipClass;
            $data['strengthPercent'] = $this->getStrengthPercent();
        }
        if ($this->location) {
            $data['location'] = $this->location;
        }
        return $data;
    }

    public function toHash()
    {
        return \sha1($this->id->toString());
    }

    public function isProbe()
    {
        return $this->getShipClass()->isProbe();
    }
}
