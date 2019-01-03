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
            $token->getPercent(),
            $token->getCost(),
        );
        $this->tokenHandler->markAsUsed($token->getOriginalToken());
        return $newHealth;
    }

    public function getSmallHealthTransaction(
        User $user,
        Ship $ship
    ): Transaction {
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
        int $percent,
        int $cost
    ): Transaction {
        $token = $this->tokenHandler->makeToken(...ShipHealthToken::make(
            $shipId,
            $userId,
            $percent,
            $cost,
        ));
        return new Transaction(
            $cost,
            new ShipHealthToken(
                $token->getJsonToken(),
                (string)$token,
                TokenProvider::getActionPath(ShipHealthToken::class, $this->dateTimeFactory->now())
            ),
            0,
            $percent,
        );
    }

    private function updateHealth(
        UuidInterface $userId,
        UuidInterface $shipId,
        int $percent, // todo - this should not be percentage, as its too strong?
        int $cost
    ): int
    {
        // get the ship and its class to determine health improvement
        /** @var \App\Data\Database\Entity\Ship $ship */
        $ship = $this->entityManager->getShipRepo()->getShipForOwnerId($shipId, $userId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        $userRepo = $this->entityManager->getUserRepo();
        $userEntity = $userRepo->getByID($userId, Query::HYDRATE_OBJECT);

        $maxStrength = $ship->shipClass->strength;
        $amountToAdd = ($percent / 100) * $maxStrength;
        $currentStrength = $ship->strength;

        if (($currentStrength + $amountToAdd) > $maxStrength) {
            $amountToAdd = $maxStrength - $currentStrength;
        }

        $newStrength = $this->entityManager->transactional(function() use ($ship, $amountToAdd, $userEntity, $cost) {
            $newStrength = $this->entityManager->getShipRepo()->updateStrengthValue(
                $ship,
                (int)\ceil($amountToAdd)
            );
            $this->consumeCredits($userEntity, $cost);
            return $newStrength;
        });

        return $newStrength;
    }
}
