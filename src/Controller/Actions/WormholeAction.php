<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\OutdatedMoveException;
use App\Response\ShipInPortResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;

class WormholeAction
{
    public function __construct(
        private EffectsService $effectsService,
        private ShipsService $shipsService,
        private ShipInPortResponse $shipInPortResponse,
    ) {
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $error = null;

        $token = $this->effectsService->parseUseWormholeToken($tokenString);
        $this->effectsService->useWormholeEffectToken($token);

        $shipWithLocation = $this->shipsService->getByIDWithLocation($token->getShipId());
        if (!$shipWithLocation) {
            throw new \RuntimeException('Something went very wrong. Ship was not found');
        }
        $data = $this->shipInPortResponse->getResponseData(
            $shipWithLocation->getOwner(),
            $shipWithLocation,
            $shipWithLocation->getLocation(),
        );

        return [
            'data' => $data,
        ];
    }
}
