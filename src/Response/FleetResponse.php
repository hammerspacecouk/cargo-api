<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Service\EventsService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;

class FleetResponse
{
    private $eventsService;
    private $shipsService;
    private $shipNameService;

    public function __construct(
        EventsService $eventsService,
        ShipsService $shipsService,
        ShipNameService $shipNameService
    ) {
        $this->eventsService = $eventsService;
        $this->shipsService = $shipsService;
        $this->shipNameService = $shipNameService;
    }

    public function getResponseDataForUser(User $user): array
    {
        $allShips = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 1000);

        $activeShips = \array_filter($allShips, function (Ship $ship) {
            return !$ship->isDestroyed();
        });
        $destroyedShips = \array_filter($allShips, function (Ship $ship) {
            return $ship->isDestroyed();
        });

        $activeFleetShips = \array_map(function (Ship $ship) use ($user) {
            return [
                'ship' => $ship,
                'renameToken' => $this->shipNameService->getRequestShipNameTransaction(
                    $user->getId(),
                    $ship->getId()
                ),
            ];
        }, $activeShips);

        return [
            'activeShips' => $activeFleetShips,
            'destroyedShips' => $destroyedShips,
            'events' => $this->eventsService->findLatestForUser($user),
        ];
    }
}
