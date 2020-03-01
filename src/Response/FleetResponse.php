<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
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
        $allShips = $this->shipsService->getForOwnerIDWithLocation($user->getId());

        $fleetShips = \array_map(function (Ship $ship) {
            return $this->mapShip($ship);
        }, $allShips);

        // order the ships. At the top should be those that need attention.
        \usort($fleetShips, static function ($a, $b) {
            /** @var Ship $shipA */
            $shipA = $a['ship'];
            /** @var Ship $shipB */
            $shipB = $b['ship'];

            // damaged ships to the bottom
            if ($shipA->isDestroyed() !== $shipB->isDestroyed()) {
                return $shipA->isDestroyed() ? 1 : -1;
            }

            // ships in danger to the top
            if ($a['needsAttention'] !== $b['needsAttention']) {
                return $a['needsAttention'] ? -1 : 1;
            }

            // otherwise order by name
            return $shipA->getName() <=> $shipB->getName();
        });

        return [
            'ships' => $fleetShips,
            'events' => $this->eventsService->findLatestForUser($user),
        ];
    }

    private function mapShip(Ship $ship): array
    {
        return [
            'ship' => $ship,
            'needsAttention' => $this->shipNeedsAttention($ship),
        ];
    }

    private function shipNeedsAttention(Ship $ship): bool
    {
        if ($ship->getLocation()->isDangerous()) {
            return true;
        }

        if (!$ship->isHealthy() && !$ship->isDestroyed()) {
            return true;
        }

        return false;
    }
}
