<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipTravelEffectToken;
use App\Response\ShipInPortResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;

class ApplyShipTravelEffectAction extends AbstractApplySimpleEffectAction
{
    private $shipInPortResponse;
    private $shipsService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(ShipTravelEffectToken::class);
    }

    public function __construct(
        ShipInPortResponse $shipInPortResponse,
        ShipsService $shipsService,
        EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        parent::__construct($effectsService, $logger);
        $this->shipInPortResponse = $shipInPortResponse;
        $this->shipsService = $shipsService;
    }

    public function getResponse(GenericApplyEffectToken $token): array
    {
        $shipWithLocation = $this->shipsService->getByIDWithLocation($token->getShipId());
        if (!$shipWithLocation) {
            throw new \RuntimeException('Something went very wrong. Ship was not found');
        }

        return [
            'data' => $this->shipInPortResponse->getResponseData(
                $shipWithLocation->getOwner(),
                $shipWithLocation,
                $shipWithLocation->getLocation(),
                ),
            'error' => null,
        ];
    }
}
