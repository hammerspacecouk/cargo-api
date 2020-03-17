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
    private ShipsService $shipsService;
    private ApplicationConfig $applicationConfig;
    private ChannelsService $channelsService;
    private ShipLocationsService $shipLocationsService;

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
        $builder = new MapBuilder($this->applicationConfig->getApiHostname(), $this->user->getRotationSteps());
        // get all the current ship locations
        $playerShips = $this->shipsService->getForOwnerIDWithLocation($this->user->getId());
        foreach ($playerShips as $ship) {
            $location = $ship->getLocation();
            if ($location instanceof ShipInPort) {
                $port = $location->getPort();
                $builder->addPort($port, true);
                $builder->addShipInPort($ship, $port);
                $builder = $this->addNearbyPlanets($builder, $port);
                $builder = $this->addShipHistory($builder, $ship);
            } elseif ($location instanceof ShipInChannel) {
                // todo
            }
        }


        return [
            'map' => $builder,
        ];
    }

    private function addNearbyPlanets(MapBuilder $builder, Port $port): MapBuilder
    {
        $nearby = $this->channelsService->getAllLinkedToPort($port);
        foreach ($nearby as $nearbyChannel) {
            $builder->addLink($nearbyChannel);
        }
        return $builder;
    }

    private function addShipHistory(MapBuilder $builder, Ship $ship): MapBuilder
    {
        // get the ship's last 5 moves. draw lines and planets in decreasing opacity
        $latestLocations = $this->shipLocationsService->getRecentForShip($ship);
        $ports = array_map(static function (ShipInPort $shipInPort) {
            return $shipInPort->getPort();
        }, $latestLocations);

        $builder->addShipHistory($ship, $ports);
        return $builder;
    }
}
