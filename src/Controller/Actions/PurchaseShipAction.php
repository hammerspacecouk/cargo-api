<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\UpgradesResponse;
use App\Service\UpgradesService;
use App\Service\UsersService;

class PurchaseShipAction
{
    private $upgradesService;
    private $usersService;
    private $upgradesResponse;

    public function __construct(
        UpgradesService $upgradesService,
        UpgradesResponse $upgradesResponse,
        UsersService $usersService
    ) {
        $this->upgradesService = $upgradesService;
        $this->usersService = $usersService;
        $this->upgradesResponse = $upgradesResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $purchaseShipToken = $this->upgradesService->parsePurchaseShipToken($tokenString);
        $message = $this->upgradesService->usePurchaseShipToken($purchaseShipToken);

        $user = $this->usersService->getById($purchaseShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        // send back the new state of the upgrades
        $data = [
            'message' => $message,
            'newScore' => $user->getScore(),
            'upgrades' => $this->upgradesResponse->getResponseDataForUser($user),
        ];
        return $data;
    }
}
