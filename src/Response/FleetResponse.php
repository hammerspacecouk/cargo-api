<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Service\EffectsService;
use App\Service\EventsService;
use App\Service\Ships\ShipHealthService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;

class FleetResponse
{
    private $eventsService;
    private $shipsService;
    private $shipHealthService;
    private $shipNameService;
    private $effectsService;

    public function __construct(
        EffectsService $effectsService,
        EventsService $eventsService,
        ShipsService $shipsService,
        ShipHealthService $shipHealthService,
        ShipNameService $shipNameService
    ) {
        $this->eventsService = $eventsService;
        $this->shipsService = $shipsService;
        $this->shipHealthService = $shipHealthService;
        $this->shipNameService = $shipNameService;
        $this->effectsService = $effectsService;
    }

    public function getResponseDataForUser(User $user): array
    {
        $allShips = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 1000);

        $fleetShips = \array_map(function (Ship $ship) use ($user) {
            return $this->mapShip($ship, $user);
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

    private function mapShip(Ship $ship, User $user): array
    {
        $renameToken = $this->shipNameService->getRequestShipNameTransaction(
            $user->getId(),
            $ship->getId(),
        );

        return [
            'ship' => $ship,
            'needsAttention' => $this->shipNeedsAttention($ship),
            'defenceOptions' => $this->effectsService->getShipDefenceOptions($ship, $user),
            'renameToken' => $renameToken,
            'health' => [
                $this->shipHealthService->getSmallHealthTransaction($user, $ship),
                $this->shipHealthService->getLargeHealthTransaction($user, $ship),
            ],
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
