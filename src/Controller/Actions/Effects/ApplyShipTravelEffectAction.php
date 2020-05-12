<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Response\ShipInPortResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;

class ApplyShipTravelEffectAction extends AbstractApplySimpleEffectAction
{
    private ShipInPortResponse $shipInPortResponse;
    private ShipsService $shipsService;

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
