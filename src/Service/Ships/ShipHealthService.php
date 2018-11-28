<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Costs;
use App\Domain\ValueObject\Token\Action\ShipHealthToken;
use App\Domain\ValueObject\Transaction;
use App\Service\ShipsService;
use Ramsey\Uuid\UuidInterface;

class ShipHealthService extends ShipsService
{
    public function parseShipHealthToken(
        string $tokenString
    ): ShipHealthToken {
        return new ShipHealthToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useShipHealthToken(
        ShipHealthToken $token
    ): string {
        // todo - real service
        $name = $this->updateHealth($token->getUserId(), $token->getShipId(), $token->getPercent());
        $this->tokenHandler->markAsUsed($token->getOriginalToken());
        return $name;
    }

    public function getSmallHealthTransaction(
        User $user,
        Ship $ship
    ): Transaction {
        return $this->getShipHealthTransaction(
            $user->getId(),
            $ship->getId(),
            Costs::SMALL_HEALTH_INCREASE,
            Costs::SMALL_HEALTH
        );
    }

    public function getLargeHealthTransaction(
        User $user,
        Ship $ship
    ): Transaction {
        return $this->getShipHealthTransaction(
            $user->getId(),
            $ship->getId(),
            Costs::LARGE_HEALTH_INCREASE,
            Costs::LARGE_HEALTH
        );
    }

    private function getShipHealthTransaction(
        UuidInterface $userId,
        UuidInterface $shipId,
        int $percent,
        int $cost
    ): Transaction {
        $token = $this->tokenHandler->makeToken(...ShipHealthToken::make(
            $shipId,
            $userId,
            $percent,
            $cost
        ));
        return new Transaction(
            $cost,
            new ShipHealthToken($token->getJsonToken(), (string)$token),
            0,
            $percent
        );
    }
}
