<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Service\EventsService;
use App\Service\ShipsService;

class FleetResponse
{
    private $eventsService;
    private $shipsService;

    public function __construct(
        EventsService $eventsService,
        ShipsService $shipsService
    ) {
        $this->eventsService = $eventsService;
        $this->shipsService = $shipsService;
    }

    public function getResponseDataForUser(User $user): array
    {
        $allShips = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 1000);

        $fleetShips = \array_map(function (Ship $ship) {
            return [
                'ship' => $ship,
                'needsAttention' => $this->shipNeedsAttention($ship),
            ];
        }, $allShips);

        // order the ships. At the top should be those that need attention.
        // At the bottom should be the destroyed ships
        // the rest should be ordered by name
        \usort($fleetShips, function ($a, $b) {
            /** @var Ship $shipA */
            $shipA = $a['ship'];
            /** @var Ship $shipB */
            $shipB = $b['ship'];

            if ($a['needsAttention'] !== $b['needsAttention']) {
                return (int)$a['needsAttention'] - (int)$b['needsAttention'];
            }

            if ($shipA->isDestroyed() !== $shipB->isDestroyed()) {
                return (int)$shipB->isDestroyed() - (int)$shipA->isDestroyed();
            }

            return \strcmp($shipA->getName(), $shipB->getName());
        });

        return [
            'ships' => $fleetShips,
            'events' => $this->eventsService->findLatestForUser($user),
        ];
    }

    private function shipNeedsAttention(Ship $ship): bool
    {
        if ($ship->getLocation()->isDangerous()) {
            return true;
        }

        if (!$ship->isHealthy()) {
            return true;
        }

        return false;
    }


}
