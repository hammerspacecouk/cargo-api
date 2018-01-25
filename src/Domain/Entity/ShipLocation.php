<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

interface ShipLocation
{
    public const STATUS_DOCKED = 'DOCKED';
    public const STATUS_SAILING = 'SAILING';

    public function getShip(): Ship;

    public function getEntryTime(): DateTimeImmutable;

    public function getStatus(): string;
}
