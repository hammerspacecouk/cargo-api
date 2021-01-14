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
use App\Service\PortsService;
use App\Service\ShipLocationsService;
use App\Service\ShipsService;
use App\Service\MapBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class MapAction extends AbstractUserAction
{
    public static function getRouteDefinition(): Route
    {
        return new Route('/play/map', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        private ApplicationConfig $applicationConfig,
        private ChannelsService $channelsService,
        private ShipLocationsService $shipLocationsService,
        private ShipsService $shipsService,
        private PortsService $portsService,
        AuthenticationService $authenticationService,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
    }

    public function invoke(Request $request): array
    {
        $builder = new MapBuilder($this->applicationConfig->getApiHostname(), $this->user->getRotationSteps());
        // get all the current ship locations
        $playerShips = $this->shipsService->getForOwnerIDWithLocation($this->user->getId());
        $allChannels = $this->channelsService->getAll();
        $allVisitedPortIds = array_flip(array_map(
            static fn($port) => $port->getId()->toString(),
            $this->portsService->findAllVisitedPortsForUserId($this->user->getId()),
        ));
        $recentlyVisitedPortIds = [];

        foreach ($playerShips as $ship) {
            if ($ship->isDestroyed()) {
                continue;
            }
            $location = $ship->getLocation();
            if ($location instanceof ShipInPort) {
                $port = $location->getPort();
                $builder->addPort($port, true, true);
                $builder->addShipInPort($ship, $port);
            } elseif ($location instanceof ShipInChannel) {
                $builder->addShipInChannel($ship, $location);
            }

            $recentPorts = $this->shipLocationsService->getRecentForShip(
                $ship,
                $this->user->getMarket()->getHistory() + 1
            );
            foreach ($recentPorts as $recentPort) {
                $recentlyVisitedPortIds[$recentPort->getPort()->getId()->toString()] = true;
            }
        }

        foreach ($allChannels as $channel) {
            $fromId = $channel->getOrigin()->getId()->toString();
            $toId = $channel->getDestination()->getId()->toString();
            if (!isset($recentlyVisitedPortIds[$fromId]) && !isset($recentlyVisitedPortIds[$toId])) {
                // unvisited channel. move on. don't include either planet
                continue;
            }

            $builder->addPort($channel->getOrigin(), false, isset($allVisitedPortIds[$fromId]));
            $builder->addPort($channel->getDestination(), false, isset($allVisitedPortIds[$toId]));
            $builder->addLink($channel);
        }
        return [
            'map' => $builder,
        ];
    }
}
