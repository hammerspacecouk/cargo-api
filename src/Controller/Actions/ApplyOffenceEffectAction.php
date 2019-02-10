<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\OutdatedMoveException;
use App\Domain\ValueObject\Token\Action\UseOffenceEffectToken;
use App\Response\ShipInPortResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;

class ApplyOffenceEffectAction extends AbstractAction
{
    private $shipsService;
    private $shipInPortResponse;
    private $effectsService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(UseOffenceEffectToken::class);
    }

    public function __construct(
        EffectsService $effectsService,
        ShipsService $shipsService,
        ShipInPortResponse $shipInPortResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipInPortResponse = $shipInPortResponse;
        $this->effectsService = $effectsService;
        $this->shipsService = $shipsService;
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
