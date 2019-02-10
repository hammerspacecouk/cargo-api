<?php
declare(strict_types=1);

namespace App\Controller\Actions\PortActions;

use App\Domain\ValueObject\Token\Action\MoveCrate\AbstractMoveCrateToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\PickupCrateToken;

class PickupCrateAction extends AbstractPortAction
{
    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(PickupCrateToken::class);
    }

    protected function parseToken(string $tokenString, bool $confirmSingleUse = true): AbstractMoveCrateToken
    {
        return $this->cratesService->parsePickupCrateToken($tokenString, $confirmSingleUse);
    }

    protected function useToken(AbstractMoveCrateToken $token): void
    {
        if (!$token instanceof PickupCrateToken) {
            throw new \InvalidArgumentException('Wrong token type');
        }

        $this->cratesService->usePickupCrateToken($token);
    }
}
