<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Coordinate;

class MapBuilder implements \JsonSerializable
{
    public const GRID_WIDTH = 208;
    private const PORT_RADIUS = 16;
    private const SHIP_SIZE = 12;
    private const SPACING = 8;
    private const JOURNEY_COLOURS = [
        '73,184,139',
        '171,134,208',
        '231,86,74',
        '255,195,20',
        '103,115,228',
        '223,134,75',
    ];

    private array $ports = [];
    private array $highlights = [];
    private array $shipsInPorts = [];
    private array $links = [];

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
            'svg' => $this->output(),
            'viewBox' => $this->getViewBox(),
            'center' => $this->getCenter()
        ];
    }

    private function getCenter(): Coordinate
    {
        return new Coordinate(0, 0);
    }

    private function getViewBox(): string
    {
        return '';
    }

    private function output(): string
    {
        return '';
    }

    public function addPort(Port $port, bool $isHighlighted = false): void
    {
        $portKey = $port->getId()->toString();
        $this->ports[$portKey] = $port;
        if ($isHighlighted) {
            $this->highlights[$portKey] = $port;
        }
    }

    public function addShipInPort(Ship $ship, Port $port): void
    {
        $portKey = $port->getId()->toString();
        if (!isset($this->shipsInPorts[$portKey])) {
            $this->shipsInPorts[$portKey] = [];
        }
        $this->shipsInPorts[$portKey][] = $ship;
    }

    public function addShipInChannel(Ship $ship, Channel $channel): void
    {
    }

    public function addLink(Channel $channel): void
    {
        // both ports should be in the list of ports
        $this->addPort($channel->getOrigin());
        $this->addPort($channel->getDestination());

        $channelKey = $channel->getId()->toString();
        $this->links[$channelKey] = $channel;
    }
}
