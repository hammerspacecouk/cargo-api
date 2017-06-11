<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject;

use App\Domain\Entity\ShipLocation;

class Travelling implements \JsonSerializable, ShipLocation
{
    public function jsonSerialize(): string
    {
        return 'TRAVELLING';
    }
}
