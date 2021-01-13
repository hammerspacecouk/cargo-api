<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\OutdatedMoveException;
use App\Response\ShipInPortResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;

class ApplyOffenceEffectAction
{
    public function __construct(
        private EffectsService $effectsService,
        private ShipsService $shipsService,
        private ShipInPortResponse $shipInPortResponse,
        private LoggerInterface $logger
    ) {
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [OFFENCE]');
        $error = null;

        $token = $this->effectsService->parseUseOffenceEffectToken($tokenString);
        try {
            $this->effectsService->useOffenceEffectToken($token);
        } catch (OutdatedMoveException $exception) {
            $error = $exception->getMessage();
        }

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
            'error' => $error,
        ];
    }
}
