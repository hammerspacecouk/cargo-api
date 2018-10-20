<?php
declare(strict_types=1);

namespace App\Controller\Actions\PortActions;

use App\Domain\ValueObject\Token\Action\MoveCrate\AbstractMoveCrateToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\PickupCrateToken;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class PickupCrateAction extends AbstractPortAction
{
    protected function parseToken(string $tokenString): AbstractMoveCrateToken
    {
        $token = $this->cratesService->parsePickupCrateToken($tokenString);

        if (!$this->cratesService->crateIsInPort($token->getCrateId(), $token->getPortId())) {
            throw new GoneHttpException('Ship is no longer in this port'); // todo - handle on front end
        }
        return $token;
    }

    protected function useToken(AbstractMoveCrateToken $token): void
    {
        if (!$token instanceof PickupCrateToken) {
            throw new \InvalidArgumentException('Wrong token type');
        }
        $this->cratesService->usePickupCrateToken($token);
    }
}
