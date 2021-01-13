<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\FleetResponse;
use App\Response\ShipInLocationResponse;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class JoinConvoyAction
{
    public function __construct(
        private UsersService $usersService,
        private ShipsService $shipsService,
        private FleetResponse $fleetResponse,
        private ShipInLocationResponse $shipInLocationResponse,
        private LoggerInterface $logger
    ) {
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [JOIN CONVOY]');

        $token = $this->shipsService->parseJoinConvoyToken($tokenString);
        $this->shipsService->useJoinConvoyToken($token);

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
