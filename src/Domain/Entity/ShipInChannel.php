<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class ShipInChannel extends AbstractShipLocation
{
    private ?Port $origin;
    private ?Port $destination;
    private DateTimeImmutable $exitTime;

    public function __construct(
        UuidInterface $id,
        ?Ship $ship,
        DateTimeImmutable $entryTime,
        DateTimeImmutable $exitTime,
        ?Port $origin,
        ?Port $destination
    ) {
        parent::__construct($id, $ship, $entryTime);
        $this->origin = $origin;
        $this->destination = $destination;
        $this->exitTime = $exitTime;
    }

    public function getStatus(): string
    {
        return self::STATUS_SAILING;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'type' => 'Channel',
            'startTime' => DateTimeFactory::toJson($this->getEntryTime()),
            'arrival' => DateTimeFactory::toJson($this->getExitTime()),
            'travelTime' => $this->getTravelTime(),
        ];
        if ($this->destination) {
            $data['destination'] = $this->getDestination();
        }
        return $data;
    }

    public function getOrigin(): Port
    {
        if ($this->origin === null) {
            throw new DataNotFetchedException('Data for Origin Port was not fetched');
        }
        return $this->origin;
    }

    public function getDestination(): Port
    {
        if ($this->destination === null) {
            throw new DataNotFetchedException('Data for Destination Port was not fetched');
        }
        return $this->destination;
    }

    public function getExitTime(): DateTimeImmutable
    {
        return $this->exitTime;
    }

    private function getTravelTime(): int
    {
        return ($this->getExitTime()->getTimestamp() - $this->getEntryTime()->getTimestamp());
    }

    public function isDangerous(): bool
    {
        // you can't be hurt while travelling
        return false;
    }
}
