<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\ShipClass;
use App\Data\TokenProvider;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\LockedTransaction;
use App\Domain\ValueObject\ShipLaunchEvent;
use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use App\Domain\ValueObject\Token\Action\PurchaseShipToken;
use App\Domain\ValueObject\Transaction;
use Doctrine\ORM\Query;

class UpgradesService extends AbstractService
{
    public function getAvailableShipsForUser(User $user): array
    {
        // get the full list, then blank out any that aren't met by the rank
        $allClasses = $this->entityManager->getShipClassRepo()->getList();

        // get the counts for all the ship types for this user
        $shipCountsByClassId = $this->entityManager->getShipRepo()->countClassesForUserId($user->getId());

        $mapper = $this->mapperFactory->createShipClassMapper();
        return array_map(function ($result) use ($user, $mapper, $shipCountsByClassId): ?Transaction {
            $mapped = $mapper->getShipClass($result);
            if (!$user->getRank()->meets($mapped->getMinimumRank())) {
                return new LockedTransaction($mapped->getMinimumRank());
            }

            $alreadyOwned = $shipCountsByClassId[(string)$mapped->getId()] ?? 0;

            $rawToken = $this->tokenHandler->makeToken(...PurchaseShipToken::make(
                $user->getId(),
                $mapped->getId()
            ));

            return new Transaction(
                $mapped->getPurchaseCost(),
                new PurchaseShipToken(
                    $rawToken->getJsonToken(),
                    (string)$rawToken,
                    TokenProvider::getActionPath(PurchaseShipToken::class, $this->dateTimeFactory->now())
                ),
                $alreadyOwned,
                $mapped
            );
        }, $allClasses);
    }

    public function getAvailableEffectsByDisplayTypeForUserAndPort(
        User $user,
        Port $port,
        Ship $ship,
        string $type
    ): array {
        // todo - use $port to reduce the list

        // get the full list, then blank out any that aren't met by the rank
        $allEffectsGrouped = $this->entityManager->getEffectRepo()->getAllPurchasableByDisplayType($type);

        $mapper = $this->mapperFactory->createEffectMapper();
        return array_map(function ($result) use ($user, $ship, $mapper): ?Transaction {
            $mapped = $mapper->getEffect($result);
            if (!$user->getRank()->meets($mapped->getMinimumRank())) {
                return null;
            }

            $purchaseToken = null;
            $rawToken = $this->tokenHandler->makeToken(...PurchaseEffectToken::make(
                $user->getId(),
                $mapped->getId(),
                $ship->getId(),
                $mapped->getPurchaseCost(),
            ));
            $purchaseToken = new PurchaseEffectToken(
                $rawToken->getJsonToken(),
                (string)$rawToken,
                TokenProvider::getActionPath(PurchaseEffectToken::class, $this->dateTimeFactory->now())
            );

            return new Transaction(
                $mapped->getPurchaseCost(),
                $purchaseToken,
                $this->entityManager->getUserEffectRepo()->countForUserId($mapped->getId(), $user->getId()),
                $mapped
            );
        }, $allEffectsGrouped);
    }

    public function parsePurchaseShipToken(
        string $tokenString
    ): PurchaseShipToken {
        return new PurchaseShipToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function usePurchaseShipToken(
        PurchaseShipToken $token
    ): ShipLaunchEvent {

        // get the owner and their home port
        /** @var \App\Data\Database\Entity\User $userEntity */
        $userEntity = $this->entityManager->getUserRepo()
            ->getByIDWithHomePort($token->getOwnerId(), Query::HYDRATE_OBJECT);

        // get the ship class and a ship name
        /** @var ShipClass $shipClassEntity */
        $shipClassEntity = $this->entityManager->getShipClassRepo()
            ->getByID($token->getShipClassId(), Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();

        // make a new ship, attached to the owner
        // place the new ship in the users home port
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->logger->info('Making new ship');
            $ship = $this->entityManager->getShipRepo()->createNewShip($shipName, $shipClassEntity, $userEntity);

            $this->logger->info('Placing ship in home port');
            $this->entityManager->getShipLocationRepo()->makeInPort($ship, $userEntity->homePort);

            $this->consumeCredits($userEntity, $shipClassEntity->purchaseCost);
            $this->tokenHandler->markAsUsed($token->getOriginalToken());

            $this->logger->info('Committing transaction');
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Rolled back ' . __METHOD__ . ' transaction');
            throw $e;
        }

        $shipEntity = $this->mapperFactory->createShipMapper()->getShip(
            $this->entityManager->getShipRepo()->getByID($ship->id)
        );
        $portEntity = $this->mapperFactory->createPortMapper()->getPort(
            $this->entityManager->getPortRepo()->getByID($userEntity->homePort->id)
        );
        return new ShipLaunchEvent($shipEntity, $portEntity);
    }

    public function parsePurchaseEffectToken(string $tokenString): PurchaseEffectToken
    {
        return new PurchaseEffectToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function usePurchaseEffectToken(PurchaseEffectToken $purchaseEffectToken): void
    {
        $userEntity = $this->entityManager->getUserRepo()
            ->getByID($purchaseEffectToken->getOwnerId(), Query::HYDRATE_OBJECT);
        $effectEntity = $this->entityManager->getEffectRepo()
            ->getByID($purchaseEffectToken->getEffectId(), Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function () use ($userEntity, $effectEntity, $purchaseEffectToken) {

            // add to effect to the user's effects list
            $this->entityManager->getUserEffectRepo()->createNew($effectEntity, $userEntity);

            // update the user's balance
            $this->consumeCredits($userEntity, (int)$purchaseEffectToken->getCost());

            // consume the token
            $this->tokenHandler->markAsUsed($purchaseEffectToken->getOriginalToken());
        });
    }
}
