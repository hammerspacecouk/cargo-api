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

    public function __construct(
        UuidInterface $id,
        string $name,
        ?ShipClass $shipClass,
        ?ShipLocation $location = null
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->shipClass = $shipClass;
        $this->location = $location;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocation(): ?ShipLocation
    {
        if ($this->location === null) {
            throw new DataNotFetchedException(
                'Tried to use the crate location, but it was not fetched'
            );
        }
        return $this->location;
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
        if ($this->shipClass) {
            $data['class'] = $this->shipClass;
        }
        if ($this->location) {
            $data['location'] = $this->location;
        }
        return $data;
    }
}
