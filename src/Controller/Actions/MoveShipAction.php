<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\ShipInChannelResponse;
use App\Service\EffectsService;
use App\Service\Ships\ShipMovementService;
use App\Service\UsersService;

class MoveShipAction
{
    private $shipMovementService;
    private $usersService;
    private $shipInChannelResponse;
    private $effectsService;

    public function __construct(
        ShipMovementService $shipMovementService,
        UsersService $usersService,
        EffectsService $effectsService,
        ShipInChannelResponse $shipInChannelResponse
    ) {
        $this->shipMovementService = $shipMovementService;
        $this->usersService = $usersService;
        $this->shipInChannelResponse = $shipInChannelResponse;
        $this->effectsService = $effectsService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $moveShipToken = $this->shipMovementService->parseMoveShipToken($tokenString);
        $newChannelLocation = $this->shipMovementService->useMoveShipToken($moveShipToken);

        // send back the new state of the ship in the channel and the new state of the user
        $user = $this->usersService->getById($moveShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        return [
            'data' => $this->shipInChannelResponse->getResponseData(
                $user,
                $this->shipMovementService->getByID($moveShipToken->getShipId()),
                $newChannelLocation,
                $this->effectsService->addRandomEffectsForUser($user)
            ),
            'error' => null,
        ];
    }
}
