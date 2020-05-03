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
    private ShipsService $shipsService;
    private ShipInPortResponse $shipInPortResponse;
    private EffectsService $effectsService;
    private LoggerInterface $logger;

    public function __construct(
        EffectsService $effectsService,
        ShipsService $shipsService,
        ShipInPortResponse $shipInPortResponse,
        LoggerInterface $logger
    ) {
        $this->shipInPortResponse = $shipInPortResponse;
        $this->effectsService = $effectsService;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
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
