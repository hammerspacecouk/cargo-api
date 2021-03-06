<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Service\EffectsService;
use Psr\Log\LoggerInterface;

abstract class AbstractApplySimpleEffectAction
{
    public function __construct(
        private EffectsService $effectsService,
        protected LoggerInterface $logger
    ) {
    }

    abstract public function getResponse(GenericApplyEffectToken $token): array;

    // apply effects that just populate the list for reading later
    public function invoke(string $tokenString): array
    {
        $applyEffectToken = $this->effectsService->parseApplySimpleEffectToken($tokenString);
        $this->effectsService->useSimpleEffectToken(
            $applyEffectToken,
            static::class === ApplyShipDefenceEffectAction::class,
            static::class === ApplyShipTravelEffectAction::class,
        );

        return $this->getResponse($applyEffectToken);
    }
}
