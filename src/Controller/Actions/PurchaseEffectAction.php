<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\ShipInLocationResponse;
use App\Service\ShipsService;
use App\Service\UpgradesService;
use App\Service\UsersService;

class PurchaseEffectAction
{
    private UpgradesService $upgradesService;
    private UsersService $usersService;
    private ShipInLocationResponse $shipInLocationResponse;
    private ShipsService $shipsService;

    public function __construct(
        UpgradesService $upgradesService,
        ShipsService $shipsService,
        UsersService $usersService,
        ShipInLocationResponse $shipInLocationResponse
    ) {
        $this->upgradesService = $upgradesService;
        $this->usersService = $usersService;
        $this->shipInLocationResponse = $shipInLocationResponse;
        $this->shipsService = $shipsService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $purchaseEffectToken = $this->upgradesService->parsePurchaseEffectToken($tokenString);
        $this->upgradesService->usePurchaseEffectToken($purchaseEffectToken);

        $user = $this->usersService->getById($purchaseEffectToken->getOwnerId());
        $ship = $this->shipsService->getByIDWithLocation($purchaseEffectToken->getShipId());
        if (!$user || !$ship) {
            throw new \RuntimeException('Something went very wrong.');
        }

        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $ship),
        ];
    }
}
