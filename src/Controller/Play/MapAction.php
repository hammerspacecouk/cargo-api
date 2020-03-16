<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\AbstractUserAction;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInChannel;
use App\Domain\Entity\ShipInPort;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\ChannelsService;
use App\Service\ShipLocationsService;
use App\Service\ShipsService;
use App\Service\MapBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class MapAction extends AbstractUserAction
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

    /** @var ShipsService */
    private $shipsService;
    /**
     * @var ApplicationConfig
     */
    private $applicationConfig;
    /**
     * @var ChannelsService
     */
    private $channelsService;
    /**
     * @var ShipLocationsService
     */
    private $shipLocationsService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/map', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ChannelsService $channelsService,
        ShipLocationsService $shipLocationsService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->shipsService = $shipsService;
        $this->applicationConfig = $applicationConfig;
        $this->channelsService = $channelsService;
        $this->shipLocationsService = $shipLocationsService;
    }

    public function invoke(Request $request): array
    {
        // get all the current ship locations
        $playerShips = $this->shipsService->getForOwnerIDWithLocation($this->user->getId());
        $ports = [];
        $ships = [];
        $highlights = [];
        $nearbyLinks = [];
        $journeyHistory = [];
        foreach ($playerShips as $i => $ship) {
            $location = $ship->getLocation();
            if ($location instanceof ShipInPort) {
                $port = $location->getPort();

                $coordinates = $port->getCoordinates($this->user->getRotationSteps());

                $y = 0 - (self::GRID_WIDTH / 4) - (self::SHIP_SIZE / 2);
                if (isset($ports[$port->getId()->toString()])) {
                    // todo - this is a very crude method. should instead spread them out around the circle
                    $y = (self::GRID_WIDTH / 4) + (self::SHIP_SIZE / 2);
                }

                // draw the current planet
                $ports[$port->getId()->toString()] = $port;
                $highlights[$port->getId()->toString()] = $port;


                // draw the ship (link)
                $shipX = $coordinates[0];
                $shipY = $coordinates[1] + $y;
                $ships[] = [
                    'id' => $ship->getId()->toString(),
                    'url' => $this->applicationConfig->getApiHostname() . $ship->getShipClass()->getImagePath(),
                    'x' => $shipX,
                    'y' => $shipY,
                    'name' => $ship->getName(),
                ];

                $this->attachNearby($nearbyLinks, $ports, $port, $coordinates);
                $this->attachHistory(
                    $journeyHistory,
                    $ports,
                    self::JOURNEY_COLOURS[$i % count(self::JOURNEY_COLOURS)],
                    $ship,
                    $shipX,
                    $shipY
                );
            }
        }


        // what about when it's in transit

        $firstPort = $ports[array_key_first($ports)];

        $builder = new MapBuilder($this->applicationConfig->getApiHostname(), $this->user->getRotationSteps());
        // get all the current ship locations
        $playerShips = $this->shipsService->getForOwnerIDWithLocation($this->user->getId());
        foreach ($playerShips as $ship) {
            $location = $ship->getLocation();
            if ($location instanceof ShipInPort) {
                $port = $location->getPort();
                $builder->addPort($port, true);
                $builder->addShipInPort($ship, $port);

                $builder = $this->addNearby($builder, $port);
            } elseif ($location instanceof ShipInChannel) {
                // todo
            }
        }


        return [
            'svg' => $this->buildSvg($ports, $ships, $highlights, $nearbyLinks, $journeyHistory),
            'viewBox' => $firstPort->getViewBox(),
            'centerX' => $firstPort->getCoordinates($this->user->getRotationSteps())[0],
            'centerY' => $firstPort->getCoordinates($this->user->getRotationSteps())[1],
            'new' => $builder,
        ];
    }

    private function addNearby(MapBuilder $builder, Port $port): MapBuilder
    {
        $nearby = $this->channelsService->getAllLinkedToPort($port);
        foreach ($nearby as $nearbyChannel) {
            $builder->addLink($nearbyChannel);
        }
        return $builder;
    }

    // todo - a way that doesn't pass by reference
    private function attachNearby(array &$nearbyLinks, array &$ports, Port $port, array $coordinates): void
    {
        // get the connecting planets and draw dotted lines to them
        $nearby = $this->channelsService->getAllLinkedToPort($port);
        foreach ($nearby as $nearbyChannel) {
            $origin = $nearbyChannel->getOrigin();
            $destination = $nearbyChannel->getDestination();
            $nearbyPort = $origin->equals($port) ? $destination : $origin;
            $ports[$nearbyPort->getId()->toString()] = $nearbyPort;
            $nearbyLinks[$nearbyChannel->getId()->toString()] = [
                'from' => $coordinates,
                'to' => $nearbyPort->getCoordinates($this->user->getRotationSteps()),
            ];
        }
    }

    // todo - a way that doesn't pass by reference
    private function attachHistory(array &$journeyHistory, array &$ports, string $color, Ship $ship, int $shipX, int $shipY): void
    {
        // get the ship's last 5 moves. draw lines and planets in decreasing opacity
        $latestLocations = $this->shipLocationsService->getRecentForShip($ship);
        array_shift($latestLocations); // remove the current planet
        $follow = [$shipX + (self::SHIP_SIZE / 2), $shipY + (self::SHIP_SIZE / 2)]; // start at ship and go backwards
        $opacity = 1;
        foreach ($latestLocations as $latestLocation) {
            if (!$latestLocation instanceof ShipInPort) {
                throw new \LogicException('This went wrong');
            }
            $port = $latestLocation->getPort();
            $ports[$port->getId()->toString()] = $port;
            $toCoords = $port->getCoordinates($this->user->getRotationSteps());
            $journeyHistory[] = [
                'from' => $follow,
                'to' => $toCoords,
                'opacity' => $opacity,
                'color' => $color,
            ];
            $follow = $toCoords;
            $opacity -= 0.2;
        }
    }

    /**
     * @param Port[] $ports
     * @param array $ships
     * @param Port[] $highlights
     * @param array $nearbyLinks
     * @param array $journeyHistory
     * @return string
     */
    private function buildSvg(
        array $ports,
        array $ships,
        array $highlights,
        array $nearbyLinks,
        array $journeyHistory
    ): string {
        $portSvgs = '';
        $shipSvgs = '';
        $lineSvgs = '';
        $highlightSvgs = '';
        $texts = '';
        $radius = self::PORT_RADIUS;
        $color = '#FFB511';
        foreach ($ports as $port) {
            $coords = $port->getCoordinates($this->user->getRotationSteps());
            if (!$coords) {
                continue;
            }
            $textX = $coords[0] + $radius + self::SPACING;
            $textY = $coords[1] + $radius + self::SPACING;

            $portSvgs .= <<<SVG
                <circle cx="$coords[0]" cy="$coords[1]" r="$radius" fill="$color" />
            SVG;
            $texts .= <<<SVG
                <text font-size="16px" x="$textX" y="$textY" fill="$color">{$port->getName()}</text>
            SVG;
        }

        foreach ($highlights as $highlight) {
            $coords = $highlight->getCoordinates($this->user->getRotationSteps());
            if (!$coords) {
                continue;
            }

            $radius = self::GRID_WIDTH / 4;
            $highlightSvgs .= <<<SVG
                <circle
                cx="$coords[0]"
                cy="$coords[1]"
                r="$radius"
                fill="none"
                stroke="rgba(255,255,255,0.5)"
                stroke-width="2px"
             />
            SVG;
        }

        foreach ($ships as $ship) {
            $size = self::SHIP_SIZE;
            $shipSvgs .= <<<SVG
                <image href="{$ship['url']}" x="{$ship['x']}" y="{$ship['y']}" width="{$size}px" height="{$size}px" />
            SVG;
            $textX = $ship['x'] + $size + (self::SPACING / 2);
            $textY = $ship['y'] + ($size * 0.75);
            $texts .= <<<SVG
                <text font-size="8px" x="$textX" y="$textY" fill="white">{$ship['name']}</text>
            SVG;
        }

        foreach ($nearbyLinks as $link) {
            $lineSvgs .= <<<SVG
                <line
                    x1="{$link['from'][0]}"
                    y1="{$link['from'][1]}"
                    x2="{$link['to'][0]}"
                    y2="{$link['to'][1]}"
                    stroke="#999"
                    stroke-dasharray="4"
                />
            SVG;
        }

        foreach ($journeyHistory as $journey) {
            $lineSvgs .= <<<SVG
                <line
                    x1="{$journey['from'][0]}"
                    y1="{$journey['from'][1]}"
                    x2="{$journey['to'][0]}"
                    y2="{$journey['to'][1]}"
                    stroke="rgba({$journey['color']},{$journey['opacity']})"
                    stroke-width="4px"
                />
            SVG;
        }

        return <<<SVG
            $lineSvgs
            $portSvgs
            $highlightSvgs
            $shipSvgs
            $texts
        SVG;
    }
}
