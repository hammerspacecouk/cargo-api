<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\FleetResponse;
use App\Response\ShipInLocationResponse;
use App\Service\Ships\ShipHealthService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class AddHealthAction
{
    private ShipHealthService $shipHealthService;
    private UsersService $usersService;
    private ShipInLocationResponse $shipInLocationResponse;
    private ShipsService $shipsService;
    private LoggerInterface $logger;
    private FleetResponse $fleetResponse;

    public function __construct(
        FleetResponse $fleetResponse,
        ShipHealthService $shipHealthService,
        UsersService $usersService,
        ShipsService $shipsService,
        ShipInLocationResponse $shipInLocationResponse,
        LoggerInterface $logger
    ) {
        $this->shipHealthService = $shipHealthService;
        $this->usersService = $usersService;
        $this->shipInLocationResponse = $shipInLocationResponse;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
        $this->fleetResponse = $fleetResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [SHIP HEALTH]');

        $token = $this->shipHealthService->parseShipHealthToken($tokenString);
        $this->shipHealthService->useShipHealthToken($token);

        $user = $this->usersService->getById($token->getUserId());
        $ship = $this->shipsService->getByIDWithLocation($token->getShipId());
        if (!$user || !$ship) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $ship),
            'fleet' => $this->fleetResponse->getResponseDataForUser($user),
        ];
    }
}
