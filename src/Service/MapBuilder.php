<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInChannel;
use App\Domain\ValueObject\Coordinate;
use JsonSerializable;
use function App\Functions\Arrays\firstItem;
use function App\Functions\Numbers\maxOf;
use function App\Functions\Numbers\minOf;

class MapBuilder implements JsonSerializable
{
    public const GRID_WIDTH = 208;

    private array $ports = [];
    private array $highlights = [];
    private array $visited = [];
    private array $shipsInPorts = [];
    private array $links = [];
    private array $travellingShips = [];

    private ?int $minX = null;
    private ?int $minY = null;
    private ?int $maxX = null;
    private ?int $maxY = null;

    private string $apiHostname;
    private int $rotationSteps;

    public function __construct(string $apiHostname, int $rotationSteps)
    {
        $this->apiHostname = $apiHostname;
        $this->rotationSteps = $rotationSteps;
    }

    public function jsonSerialize(): array
    {
        return [
            'svg' => [
                'grid' => self::GRID_WIDTH,
                'nearby' => $this->buildNearby(),
                'planets' => $this->buildPlanets(),
                'highlights' => $this->buildHighlights(),
                'ships' => $this->buildShips(),
            ],
            'viewBox' => $this->getViewBox(),
            'center' => $this->getCenter(),
        ];
    }

    private function getCenter(): Coordinate
    {
        if (!empty($this->highlights)) {
            return firstItem($this->highlights)->getCoordinates($this->rotationSteps);
        }
        if (!empty($this->ports)) {
            return firstItem($this->ports)->getCoordinates($this->rotationSteps);
        }
        return new Coordinate(0, 0);
    }

    public function getViewBox(): string
    {
        if (empty($this->ports)) {
            return '0 0 0 0';
        }
        /** @var Port $firstPort */
        $firstPort = firstItem($this->ports);
        return $firstPort->getViewBox($this->rotationSteps);
    }

    public function getPorts(): array
    {
        return array_values($this->ports);
    }

    public function getLinks(): array
    {
        return array_values($this->links);
    }

    public function addPort(Port $port, bool $isHighlighted = false, bool $visited = false): void
    {
        $portKey = $port->getId()->toString();
        $this->ports[$portKey] = $port;
        if ($isHighlighted) {
            $this->highlights[$portKey] = $port;
        }
        if ($visited) {
            $this->visited[$portKey] = $port;
        }
        $x = $port->getCoordinates($this->rotationSteps)->getX();
        $y = $port->getCoordinates($this->rotationSteps)->getY();
        $this->minX = minOf($this->minX, $x);
        $this->minY = minOf($this->minY, $y);
        $this->maxX = maxOf($this->maxX, $x);
        $this->maxY = maxOf($this->maxY, $y);
    }

    public function addShipInPort(Ship $ship, Port $port): void
    {
        $portKey = $port->getId()->toString();
        if (!isset($this->shipsInPorts[$portKey])) {
            $this->shipsInPorts[$portKey] = [];
        }
        $this->shipsInPorts[$portKey][] = ['angle' => 0, 'ship' => $ship];
        $this->shipsInPorts[$portKey] = $this->calculateOrbits($this->shipsInPorts[$portKey]);
    }

    public function addShipInChannel(Ship $ship, ShipInChannel $channel): void
    {
        // both ports should be in the list of ports
        $this->addPort($channel->getOrigin());
        $this->addPort($channel->getDestination());

        $channelKey = $channel->getId()->toString();

        $fromCoords = $channel->getOrigin()->getCoordinates($this->rotationSteps);
        $toCoords = $channel->getDestination()->getCoordinates($this->rotationSteps);
        $this->links[$channelKey] = [
            'id' => $channel->getId(),
            'from' => $fromCoords,
            'to' => $toCoords,
        ];

        $this->travellingShips[$ship->getId()->toString()] = [
            'id' => $ship->getId(),
            'name' => $ship->getName(),
            'center' => $fromCoords->middle($toCoords),
            'href' => $this->apiHostname . $ship->getShipClass()->getImagePath()
        ];
    }

    public function addLink(Channel $channel): void
    {
        // both ports should be in the list of ports
        $this->addPort($channel->getOrigin());
        $this->addPort($channel->getDestination());

        $channelKey = $channel->getId()->toString();
        $this->links[$channelKey] = [
            'id' => $channel->getId(),
            'from' => $channel->getOrigin()->getCoordinates($this->rotationSteps),
            'to' => $channel->getDestination()->getCoordinates($this->rotationSteps),
        ];
    }

    private function calculateOrbits(array $shipsInPort): array
    {
        $angleDiff = (M_PI * 2) / count($shipsInPort);
        $angle = -M_PI_2 * 0.60;
        $ships = [];
        foreach ($shipsInPort as $ship) {
            $ships[] = [
                'ship' => $ship['ship'],
                'angle' => $angle,
            ];
            $angle += $angleDiff;
        }
        return $ships;
    }

    private function buildNearby(): array
    {
        return array_values($this->links);
    }

    private function buildHighlights(): array
    {
        return array_values(array_map(function (Port $port) {
            return [
                'id' => $port->getId(),
                'coords' => $port->getCoordinates($this->rotationSteps),
            ];
        }, $this->highlights));
    }

    private function buildShips(): array
    {
        $ships = [];
        foreach ($this->shipsInPorts as $portId => $shipsInPort) {
            $port = $this->ports[$portId];
            foreach ($shipsInPort as $shipItem) {
                /** @var Ship $ship */
                $ship = $shipItem['ship'];
                $ships[] = [
                    'id' => $ship->getId(),
                    'name' => $ship->getName(),
                    'center' => $port->getCoordinates($this->rotationSteps),
                    'angle' => $shipItem['angle'],
                    'href' => $this->apiHostname . $ship->getShipClass()->getImagePath()
                ];
            }
        }
        return array_merge($ships, array_values($this->travellingShips));
    }

    private function buildPlanets(): array
    {
        return array_values(array_map(function (Port $port) {
            return [
                'coords' => $port->getCoordinates($this->rotationSteps),
                'id' => $port->getId(),
                'title' => $port->getName(),
                'isVisited' => isset($this->visited[$port->getId()->toString()]),
                'isSafe' => $port->isSafe(),
            ];
        }, $this->ports));
    }
}
