<?php
declare(strict_types=1);

namespace App\Domain\Entity\Null;

use App\Domain\Entity\CrateLocation;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;

// Inherits all the interfaces, so it can be accepted by all the domain objects
class NullEntity implements
    CrateLocation,
    ShipLocation,
    \JsonSerializable
{
    public function getShip(): Ship
    {
        throw new DataNotFetchedException('Cannot call ' . __METHOD__ . ' for a Null Object');
    }

    public function getEntryTime(): DateTimeImmutable
    {
        throw new DataNotFetchedException('Cannot call ' . __METHOD__ . ' for a Null Object');
    }

    public function getStatus(): string
    {
        throw new DataNotFetchedException('Cannot call ' . __METHOD__ . ' for a Null Object');
    }

    public function jsonSerialize()
    {
        return null;
    }
}
