<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Service\EffectsService;
use Psr\Log\LoggerInterface;

abstract class AbstractApplySimpleEffectAction
{
    private EffectsService $effectsService;
    protected LoggerInterface $logger;

    public function __construct(
        EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        $this->effectsService = $effectsService;
        $this->logger = $logger;
    }

    abstract public function getResponse(GenericApplyEffectToken $token): array;

    // apply effects that just populate the list for reading later
    public function invoke(string $tokenString): array
    {
        $applyEffectToken = $this->effectsService->parseApplySimpleEffectToken($tokenString);
        $this->effectsService->useSimpleEffectToken($applyEffectToken);

        return $this->getResponse($applyEffectToken);
    }
}
