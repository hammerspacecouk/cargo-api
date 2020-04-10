<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\FleetResponse;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use App\Service\UsersService;

class RenameShipAction
{
    private ShipsService $shipsService;
    private ShipNameService $shipNameService;
    private FleetResponse $fleetResponse;
    private UsersService $usersService;

    public function __construct(
        FleetResponse $fleetResponse,
        ShipsService $shipsService,
        ShipNameService $shipNameService,
        UsersService $usersService
    ) {
        $this->shipNameService = $shipNameService;
        $this->shipsService = $shipsService;
        $this->fleetResponse = $fleetResponse;
        $this->usersService = $usersService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $renameShipToken = $this->shipNameService->parseRenameShipToken($tokenString);
        $this->shipNameService->useRenameShipToken($renameShipToken);

        // fetch the updated ship
        $ship = $this->shipsService->getByID($renameShipToken->getShipId());
        if (!$ship) {
            throw new \RuntimeException('Something went very wrong here');
        }

        // fetch the updated data
        $user = $this->usersService->getByID($renameShipToken->getUserId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            'ship' => $ship,
            'fleet' => $this->fleetResponse->getResponseDataForUser($user),
        ];
    }
}
