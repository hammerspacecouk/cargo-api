<?php declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Port;
use App\Domain\Entity\ShipInChannel;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\ShipLocation;
use DateTimeImmutable;

class ShipLocationMapper extends Mapper
{
    public function getShipLocation(array $item): ShipLocation
    {
        $id = $item['id'];
        $entryTime = DateTimeImmutable::createFromMutable($item['entryTime']);
        $exitTime = $item['exitTime'] ? DateTimeImmutable::createFromMutable($item['exitTime']) : null;

        $ship = null;
        if (isset($item['ship'])) {
            $ship = $this->mapperFactory->createShipMapper()->getShip($item['ship']);
        }

        if (isset($item['channel'])) {
            return new ShipInChannel(
                $id,
                $ship,
                $entryTime,
                $exitTime,
                $this->getOrigin($item),
                $this->getDestination($item)
            );
        }

        if (isset($item['port'])) {
            $port = $this->mapperFactory->createPortMapper()->getPort($item['port']);

            return new ShipInPort(
                $id,
                $ship,
                $entryTime,
                $port
            );
        }

        throw new \RuntimeException('Invalid data object');
    }

    private function getOrigin(array $item): ?Port
    {
        if ($item['reverseDirection']) {
            return $this->getChannelDestination($item);
        }
        return $this->getChannelOrigin($item);
    }

    private function getChannelDestination(array $item): ?Port
    {
        if (isset($item['channel']['toPort'])) {
            return $this->mapperFactory->createPortMapper()->getPort($item['channel']['toPort']);
        }
        return null;
    }

    private function getChannelOrigin(array $item): ?Port
    {
        if (isset($item['channel']['fromPort'])) {
            return $this->mapperFactory->createPortMapper()->getPort($item['channel']['fromPort']);
        }
        return null;
    }

    private function getDestination(array $item): ?Port
    {
        if ($item['reverseDirection']) {
            return $this->getChannelOrigin($item);
        }
        return $this->getChannelDestination($item);
    }
}
