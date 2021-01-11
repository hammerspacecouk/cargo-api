<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\TokenProvider;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Costs;
use App\Domain\ValueObject\Token\Action\ShipHealthToken;
use App\Domain\ValueObject\Transaction;
use App\Service\ShipsService;
use Doctrine\ORM\Query;
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
    ): int {
        $newHealth = $this->updateHealth(
            $token->getUserId(),
            $token->getShipId(),
            $token->getAmount(),
            $token->getCost(),
        );
        $this->tokenHandler->markAsUsed($token->getOriginalToken());
        return $newHealth;
    }

    public function getSmallHealthTransaction(
        User $user,
        Ship $ship
    ): Transaction {
        if ($ship->isFullStrength()) {
            return new Transaction(
                Costs::SMALL_HEALTH,
                null,
                0,
                Costs::SMALL_HEALTH_INCREASE,
            );
        }

        return $this->getShipHealthTransaction(
            $user->getId(),
            $ship->getId(),
            Costs::SMALL_HEALTH_INCREASE,
            Costs::SMALL_HEALTH,
        );
    }

    public function getLargeHealthTransaction(
        User $user,
        Ship $ship
    ): Transaction {
        if ($ship->isFullStrength()) {
            return new Transaction(
                Costs::LARGE_HEALTH,
                null,
                0,
                Costs::LARGE_HEALTH_INCREASE,
            );
        }

        return $this->getShipHealthTransaction(
            $user->getId(),
            $ship->getId(),
            Costs::LARGE_HEALTH_INCREASE,
            Costs::LARGE_HEALTH,
        );
    }

    private function getShipHealthTransaction(
        UuidInterface $userId,
        UuidInterface $shipId,
        int $amount,
        int $cost
    ): Transaction {
        $token = $this->tokenHandler->makeToken(...ShipHealthToken::make(
            $shipId,
            $userId,
            $amount,
            $cost,
        ));
        return new Transaction(
            $cost,
            new ShipHealthToken(
                $token,
                TokenProvider::getActionPath(ShipHealthToken::class)
            ),
            0,
            $amount,
        );
    }

    private function updateHealth(
        UuidInterface $userId,
        UuidInterface $shipId,
        int $amountToAdd,
        int $cost
    ): int {
        // get the ship and its class to determine health improvement
        /** @var \App\Data\Database\Entity\Ship|null $ship */
        $ship = $this->entityManager->getShipRepo()->getShipForOwnerId($shipId, $userId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        $userRepo = $this->entityManager->getUserRepo();
        $userEntity = $userRepo->getByID($userId, Query::HYDRATE_OBJECT);

        $maxStrength = $ship->shipClass->strength;
        $currentStrength = $ship->strength;

        if (($currentStrength + $amountToAdd) > $maxStrength) {
            $amountToAdd = $maxStrength - $currentStrength;
        }

        $newStrength = $this->entityManager->transactional(function () use ($ship, $amountToAdd, $userEntity, $cost) {
            $newStrength = $this->entityManager->getShipRepo()->updateStrengthValue(
                $ship,
                (int)\ceil($amountToAdd)
            );
            $this->consumeCredits($userEntity, $cost);

            $this->entityManager->getUserAchievementRepo()->recordRepairedShip($userEntity->id);

            return $newStrength;
        });

        return (int)\ceil(($newStrength / $maxStrength) * 100);
    }
}
