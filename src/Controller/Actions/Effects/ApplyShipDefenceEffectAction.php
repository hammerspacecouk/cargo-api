<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Response\ShipInLocationResponse;
use App\Service\EffectsService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class ApplyShipDefenceEffectAction extends AbstractApplySimpleEffectAction
{
    public function __construct(
        private UsersService $usersService,
        private ShipsService $shipsService,
        private ShipInLocationResponse $shipInLocationResponse,
        EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        parent::__construct($effectsService, $logger);
    }

    public function getResponse(GenericApplyEffectToken $token): array
    {
        $user = $this->usersService->getById($token->getTriggeredById());
        $shipWithLocation = $this->shipsService->getByIDWithLocation($token->getShipId());
        if (!$user || !$shipWithLocation) {
            throw new \RuntimeException('Something went very wrong');
        }
        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $shipWithLocation),
            'error' => null,
        ];
    }
}
