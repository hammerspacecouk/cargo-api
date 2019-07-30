<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\SessionState;
use App\Response\FleetResponse;
use App\Response\UpgradesResponse;
use App\Service\PlayerRanksService;
use App\Service\UpgradesService;
use App\Service\UsersService;

class PurchaseShipAction
{
    private $upgradesService;
    private $usersService;
    private $upgradesResponse;
    private $playerRanksService;
    private $fleetResponse;

    public function __construct(
        FleetResponse $fleetResponse,
        PlayerRanksService $playerRanksService,
        UpgradesService $upgradesService,
        UpgradesResponse $upgradesResponse,
        UsersService $usersService
    ) {
        $this->upgradesService = $upgradesService;
        $this->usersService = $usersService;
        $this->upgradesResponse = $upgradesResponse;
        $this->playerRanksService = $playerRanksService;
        $this->fleetResponse = $fleetResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $purchaseShipToken = $this->upgradesService->parsePurchaseShipToken($tokenString);
        $shipLaunchEvent = $this->upgradesService->usePurchaseShipToken($purchaseShipToken);

        $user = $this->usersService->getById($purchaseShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        // send back the new state of the upgrades
        $data = [
            'launch' => $shipLaunchEvent,
            'session' => [
                // todo - abstract the fetching of this. currently duplicated
                'sessionState' => new SessionState(
                    $user,
                    $this->playerRanksService->getForUser($user)
                ),
                'fleet' => $this->fleetResponse->getResponseDataForUser($user),
            ],
            'shipsAvailable' => $this->upgradesResponse->getResponseDataForUser($user),
        ];
        return $data;
    }
}
