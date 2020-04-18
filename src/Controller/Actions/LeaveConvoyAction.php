<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\FleetResponse;
use App\Response\ShipInLocationResponse;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class LeaveConvoyAction
{
    private UsersService $usersService;
    private ShipInLocationResponse $shipInLocationResponse;
    private ShipsService $shipsService;
    private LoggerInterface $logger;
    private FleetResponse $fleetResponse;

    public function __construct(
        FleetResponse $fleetResponse,
        UsersService $usersService,
        ShipsService $shipsService,
        ShipInLocationResponse $shipInLocationResponse,
        LoggerInterface $logger
    ) {
        $this->usersService = $usersService;
        $this->shipInLocationResponse = $shipInLocationResponse;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
        $this->fleetResponse = $fleetResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [LEAVE CONVOY]');

        $token = $this->shipsService->parseLeaveConvoyToken($tokenString);
        $this->shipsService->useLeaveConvoyToken($token);

        $user = $this->usersService->getById($token->getOwnerId());
        $ship = $this->shipsService->getByIDWithLocation($token->getCurrentShipId());
        if (!$user || !$ship) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $ship),
            'fleet' => $this->fleetResponse->getResponseDataForUser($user),
        ];
    }
}
