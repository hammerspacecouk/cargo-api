<?php
declare(strict_types = 1);
namespace App\Domain\Entity\Null;

use App\Domain\Entity\CrateLocation;
use App\Domain\Entity\ShipLocation;

// Inherits all the interfaces, so it can be accepted by all the domain objects
class NullEntity implements
    CrateLocation,
    ShipLocation,
    \JsonSerializable
{
    public function jsonSerialize()
    {
        return null;
    }
}
