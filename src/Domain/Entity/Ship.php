<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\ShipClass;
use Ramsey\Uuid\UuidInterface;

class Ship extends Entity implements \JsonSerializable, CrateLocation
{
    private $name;
    private $shipClass;
    private $location;
    private $owner;

    public function __construct(
        UuidInterface $id,
        string $name,
        ?User $owner,
        ?ShipClass $shipClass,
        ?ShipLocation $location = null
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->shipClass = $shipClass;
        $this->location = $location;
        $this->owner = $owner;
    }

    public function getName(): string
    {
        return $this->name;
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
        ];
        if ($this->owner) {
            $data['owner'] = $this->owner;
        }
        if ($this->shipClass) {
            $data['shipClass'] = $this->shipClass;
        }
        if ($this->location) {
            $data['location'] = $this->location;
        }
        return $data;
    }
}
