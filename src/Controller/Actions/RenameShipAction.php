<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;

class RenameShipAction
{
    private $shipsService;
    private $shipNameService;

    public function __construct(
        ShipsService $shipsService,
        ShipNameService $shipNameService
    ) {
        $this->shipNameService = $shipNameService;
        $this->shipsService = $shipsService;
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

        return [
            'ship' => $ship,
        ];
    }
}
