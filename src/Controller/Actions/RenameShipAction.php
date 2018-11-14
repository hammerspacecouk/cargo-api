<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\FleetResponse;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;

class RenameShipAction extends AbstractAction
{
    private $shipsService;
    private $shipNameService;
    private $fleetResponse;

    public function __construct(
        ShipsService $shipsService,
        ShipNameService $shipNameService,
        FleetResponse $fleetResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipNameService = $shipNameService;
        $this->shipsService = $shipsService;
        $this->fleetResponse = $fleetResponse;
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
            'fleet' => $this->fleetResponse->getResponseDataForUser($ship->getOwner()),
        ];
    }
}
