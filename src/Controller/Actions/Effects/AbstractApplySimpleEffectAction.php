<?php
declare(strict_types=1);

namespace App\Controller\Actions\Effects;

use App\Controller\Actions\AbstractAction;
use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Service\EffectsService;
use Psr\Log\LoggerInterface;

abstract class AbstractApplySimpleEffectAction extends AbstractAction
{
    private $effectsService;

    public function __construct(
        EffectsService $effectsService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->effectsService = $effectsService;
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
