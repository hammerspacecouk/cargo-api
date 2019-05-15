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
        $allShips = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 1000);

        $fleetShips = \array_map(function (Ship $ship) use ($user) {
            return $this->mapShip($ship);
        }, $allShips);

        // order the ships. At the top should be those that need attention.
        // At the bottom should be the destroyed ships
        // the rest should be ordered by name
        \usort($fleetShips, static function ($a, $b) {
            /** @var Ship $shipA */
            $shipA = $a['ship'];
            /** @var Ship $shipB */
            $shipB = $b['ship'];

            if ($a['needsAttention'] !== $b['needsAttention']) {
                return (int)$b['needsAttention'] - (int)$a['needsAttention'];
            }

            if ($shipA->isDestroyed() !== $shipB->isDestroyed()) {
                return (int)$shipA->isDestroyed() - (int)$shipB->isDestroyed();
            }

            return \strcmp($shipA->getName(), $shipB->getName());
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
