<?php
declare(strict_types=1);

namespace App\Controller\Actions\PortActions;

use App\Domain\ValueObject\Token\Action\MoveCrate\AbstractMoveCrateToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\DropCrateToken;

class DropCrateAction extends AbstractPortAction
{
    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(DropCrateToken::class);
    }

    protected function parseToken(string $tokenString, bool $confirmSingleUse = true): AbstractMoveCrateToken
    {
        return $this->cratesService->parseDropCrateToken($tokenString, $confirmSingleUse);
    }

    protected function useToken(AbstractMoveCrateToken $token): void
    {
        if (!$token instanceof DropCrateToken) {
            throw new \InvalidArgumentException('Wrong token type');
        }
        $this->cratesService->useDropCrateToken($token);
    }
}
