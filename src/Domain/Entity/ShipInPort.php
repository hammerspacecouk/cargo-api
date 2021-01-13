<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class ShipInPort extends AbstractShipLocation
{
    public function __construct(
        UuidInterface $id,
        ?Ship $ship,
        DateTimeImmutable $entryTime,
        private ?Port $port
    ) {
        parent::__construct($id, $ship, $entryTime);
    }

    public function jsonSerialize(): ?Port
    {
        return $this->port;
    }

    public function getStatus(): string
    {
        return self::STATUS_DOCKED;
    }

    public function getPort(): Port
    {
        if ($this->port === null) {
            throw new DataNotFetchedException('Data for Port was not fetched');
        }
        return $this->port;
    }

    public function isDangerous(): bool
    {
        // you can be attacked at ports that are not save havens
        return !$this->getPort()->isSafe();
    }
}
