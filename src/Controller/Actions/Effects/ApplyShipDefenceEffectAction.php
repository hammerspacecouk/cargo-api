<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use App\Response\FleetResponse;
use App\Service\EffectsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class ApplyShipDefenceEffectAction extends AbstractApplySimpleEffectAction
{
    private $fleetResponse;
    private $usersService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(ShipDefenceEffectToken::class);
    }

    public function __construct(
        FleetResponse $fleetResponse,
        UsersService $usersService,
        EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        parent::__construct($effectsService, $logger);
        $this->fleetResponse = $fleetResponse;
        $this->usersService = $usersService;
    }

    public function getResponse(GenericApplyEffectToken $token): array
    {
        $user = $this->usersService->getById($token->getTriggeredById());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }
        return $this->fleetResponse->getResponseDataForUser($user);
    }
}
