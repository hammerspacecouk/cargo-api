<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Coordinate;
use JsonSerializable;
use function App\Functions\Arrays\find;
use function App\Functions\Numbers\maxOf;
use function App\Functions\Numbers\minOf;

class MapBuilder implements JsonSerializable
{
    public const GRID_WIDTH = 208;

    private array $ports = [];
    private array $highlights = [];
    private array $shipsInPorts = [];
    private array $links = [];
    private array $history = [];

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
                'history' => $this->buildShipHistory(),
            ],
            'viewBox' => $this->getViewBox(),
            'center' => $this->getCenter(),
        ];
    }

    private function getCenter(): Coordinate
    {
        return $this->highlights[array_key_first($this->highlights)]->getCoordinates($this->rotationSteps);
    }

    private function getViewBox(): string
    {
        return $this->ports[array_key_first($this->ports)]->getViewBox();
    }

    public function addPort(Port $port, bool $isHighlighted = false): void
    {
        $portKey = $port->getId()->toString();
        $this->ports[$portKey] = $port;
        if ($isHighlighted) {
            $this->highlights[$portKey] = $port;
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

    public function addShipInChannel(Ship $ship, Channel $channel): void
    {
        // todo
    }

    public function addLink(Channel $channel): void
    {
        // both ports should be in the list of ports
        $this->addPort($channel->getOrigin());
        $this->addPort($channel->getDestination());

        $channelKey = $channel->getId()->toString();
        $this->links[$channelKey] = $channel;
    }

    public function addShipHistory(Ship $ship, array $ports): void
    {
        // all ports should be in the list of ports
        foreach ($ports as $port) {
            $this->addPort($port);
        }
        $this->history[$ship->getId()->toString()] = $ports;
    }

    private function calculateOrbits(array $shipsInPort): array
    {
        $angleDiff = (M_PI * 2) / count($shipsInPort);
        $angle = -M_PI_2;
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
        return array_values(array_map(function (Channel $channel) {
            return [
                'id' => $channel->getId(),
                'from' => $channel->getOrigin()->getCoordinates($this->rotationSteps),
                'to' => $channel->getDestination()->getCoordinates($this->rotationSteps),
            ];
        }, $this->links));
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

    private function buildShipHistory(): array
    {
        $allPaths = [];
        foreach ($this->history as $shipId => $ports) {
            $firstPort = array_shift($ports);
            $shipInPort = find(static function ($ship) use ($shipId) {
                return $ship['ship']->getId()->toString() === $shipId;
            }, $this->shipsInPorts[$firstPort->getId()->toString()]);
            $point = [
                'angle' => $shipInPort['angle'],
                'coords' => $firstPort->getCoordinates($this->rotationSteps),
            ];

            $opacity = 1;
            $shipPaths = [];
            foreach ($ports as $port) {
                $to = ['coords' => $port->getCoordinates($this->rotationSteps)];
                $shipPaths[] = [
                    'from' => $point,
                    'to' => $to,
                    'opacity' => $opacity,
                ];
                $point = $to;
                $opacity -= 0.2;
            }
            $allPaths[] = $shipPaths;
        }
        return $allPaths;
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
        return $ships;
    }

    private function buildPlanets(): array
    {
        return array_values(array_map(function (Port $port) {
            return [
                'coords' => $port->getCoordinates($this->rotationSteps),
                'id' => $port->getId(),
                'title' => $port->getName(),
            ];
        }, $this->ports));
    }
}
