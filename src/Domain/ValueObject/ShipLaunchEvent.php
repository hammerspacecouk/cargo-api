<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;

class ShipLaunchEvent implements \JsonSerializable
{
    private Ship $ship;
    private Port $port;

    public function __construct(
        Ship $ship,
        Port $homePort
    ) {
        $this->ship = $ship;
        $this->port = $homePort;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'newShip' => $this->getShip(),
            'atPort' => $this->getPort(),
        ];
    }

    public function getShip(): Ship
    {
        return $this->ship;
    }

    public function getPort(): Port
    {
        return $this->port;
    }
}
