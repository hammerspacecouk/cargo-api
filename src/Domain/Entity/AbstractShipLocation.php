<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractShipLocation extends Entity implements \JsonSerializable, ShipLocation
{
    public function __construct(
        UuidInterface $id,
        private ?Ship $ship,
        private DateTimeImmutable $entryTime
    ) {
        parent::__construct($id);
    }

    public function getEntryTime(): DateTimeImmutable
    {
        return $this->entryTime;
    }

    public function getShip(): Ship
    {
        if ($this->ship === null) {
            throw new DataNotFetchedException('Tried to get Ship but the data was not fetched');
        }
        return $this->ship;
    }
}
