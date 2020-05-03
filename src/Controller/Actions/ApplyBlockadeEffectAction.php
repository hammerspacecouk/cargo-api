<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\ShipInLocationResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class ApplyBlockadeEffectAction
{
    private UsersService $usersService;
    private ShipsService $shipsService;
    private ShipInLocationResponse $shipInLocationResponse;
    private EffectsService $effectsService;
    private LoggerInterface $logger;

    public function __construct(
        UsersService $usersService,
        ShipsService $shipsService,
        ShipInLocationResponse $shipInLocationResponse,
        EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        $this->usersService = $usersService;
        $this->shipsService = $shipsService;
        $this->shipInLocationResponse = $shipInLocationResponse;
        $this->effectsService = $effectsService;
        $this->logger = $logger;
    }

    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [BLOCKADE]');

        $applyEffectToken = $this->effectsService->parseApplySimpleEffectToken($tokenString);
        $this->effectsService->useBlockadeToken($applyEffectToken);

        $user = $this->usersService->getById($applyEffectToken->getTriggeredById());
        $shipWithLocation = $this->shipsService->getByIDWithLocation($applyEffectToken->getShipId());
        if (!$user || !$shipWithLocation) {
            throw new \RuntimeException('Something went very wrong');
        }

        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $shipWithLocation),
            'error' => null,
        ];
    }
}
