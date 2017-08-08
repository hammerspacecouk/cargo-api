<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class ShipInPort extends AbstractShipLocation
{
    private $port;

    public function __construct(
        UuidInterface $id,
        ?Ship $ship,
        DateTimeImmutable $entryTime,
        ?Port $port
    ) {
        parent::__construct($id, $ship, $entryTime);
        $this->port = $port;
    }

    public function jsonSerialize()
    {
        return null; // todo
    }

    public function getPort(): Port
    {
        if ($this->port === null) {
            throw new DataNotFetchedException('Data for Port was not fetched');
        }
        return $this->port;
    }
}
