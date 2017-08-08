<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use DateTimeImmutable;

interface ShipLocation
{
    public function getShip(): Ship;
    public function getEntryTime(): DateTimeImmutable;
}
