<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\ActiveEffect as DbActiveEffect;
use App\Data\Database\Entity\Effect as DbEffect;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\TokenProvider;
use App\Domain\Entity\ActiveEffect;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInChannel;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\Entity\UserEffect;
use App\Domain\Exception\OutdatedMoveException;
use App\Domain\ValueObject\TacticalEffect;
use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipTravelEffectToken;
use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use App\Domain\ValueObject\Token\Action\UseOffenceEffectToken;
use App\Domain\ValueObject\TokenId;
use App\Domain\ValueObject\Transaction;
use Doctrine\ORM\Query;
use function App\Functions\Arrays\ensureArray;
use function App\Functions\Arrays\find;

class EffectsService extends AbstractService
{
    private $userEffectsCache = [];

    public function getAvailableEffectsForLocation(Ship $ship, User $user, ShipLocation $shipLocation): array
    {
        $isInPort = $shipLocation instanceof ShipInPort;
        $isInChannel = $shipLocation instanceof ShipInChannel;

        $allEffects = $this->getAll();
        $userEffects = $this->getOwnedEffectsForUser($user);
        $countsOfType = $this->getCountsPerEffect($userEffects);

        /** @var DbActiveEffect[] $activeShipEffects */
        $activeShipEffects = $this->getActiveEffectsForShip($ship);

        $availableEffects = [];
        foreach ($allEffects as $effect) {
            // filter out items that can't be used in this location
            if (($isInPort && !$effect->canBeUsedInPort()) || ($isInChannel && !$effect->canBeUsedInChannel())) {
                continue;
            }

            $availableEffects[] = $this->makeTacticalEffect(
                $effect,
                $user,
                $ship,
                $shipLocation,
                $activeShipEffects,
                $userEffects,
                $countsOfType
            );
        }
        return $availableEffects;
    }

    private function makeTacticalEffect(
        Effect $effect,
        User $user,
        Ship $ship,
        ShipLocation $shipLocation,
        array $activeShipEffects,
        array $userEffects,
        array $countsOfType
    ): TacticalEffect {
        if (!$user->getRank()->meets($effect->getMinimumRank())) {
            return new TacticalEffect(null, $effect->getMinimumRank());
        }

        /** @var ActiveEffect|null $activeEffect */
        $activeEffect = find(static function (ActiveEffect $activeEffect) use ($effect) {
            return $effect->getId()->equals($activeEffect->getEffect()->getId());
        }, $activeShipEffects);

        /** @var UserEffect|null $userEffect */
        $userEffect = find(static function (UserEffect $userEffect) use ($effect) {
            return $effect->getId()->equals($userEffect->getEffect()->getId());
        }, $userEffects);

        $actionToken = null;
        $hitsRemaining = null;
        $isActive = false;
        $expiry = null;
        $shipSelect = false;
        $purchaseToken = null;

        // if it's in active effects. populate hitsRemaining or expiry
        if ($activeEffect) {
            $hitsRemaining = $activeEffect->getRemainingCount();
            $expiry = $activeEffect->getExpiry();
            $isActive = true;
        } elseif ($userEffect) {
            if ($effect instanceof Effect\OffenceEffect && $effect->affectsAllShips()) {
                if ($shipLocation instanceof ShipInPort) {
                    $actionToken = $this->getOffenceEffectToken($effect, $userEffect, $ship, $shipLocation->getPort());
                } else {
                    throw new \RuntimeException('Should not be able to make offence actions outside of a port');
                }
            } elseif ($effect instanceof Effect\DefenceEffect) {
                $actionToken = $this->getDefenceEffectToken($userEffect, $user, $ship);
            } elseif ($effect instanceof Effect\TravelEffect) {
                $actionToken = $this->getTravelEffectToken($userEffect, $user, $ship);
            }
        }

        if ($effect instanceof Effect\OffenceEffect && !$effect->affectsAllShips()) {
            $shipSelect = true; // no actionToken
        }

        if ($shipLocation instanceof ShipInPort) {
            $purchaseToken = $this->getPurchaseEffectTransaction($user, $effect, $ship, $shipLocation->getPort());
        }

        return new TacticalEffect(
            $effect,
            $effect->getMinimumRank(),
            $isActive,
            $userEffect,
            $activeEffect,
            $shipSelect,
            $countsOfType[$effect->getId()->toString()] ?? 0,
            $hitsRemaining,
            $expiry,
            $actionToken,
            $purchaseToken
        );
    }

    private function getPurchaseEffectTransaction(
        User $user,
        Effect $effect,
        Ship $ship,
        Port $port
    ): ?Transaction {
        if (!$effect->canBePurchased()) {
            return null;
        }

        $cost = $effect->getPurchaseCost();
        if ($port->isSafe()) {
            // prices are more expensive in safe places
            $cost = (int)round($cost * 2.5);
            // todo - more port rules for whether you can buy here and how much the costs differs
        }

        $rawToken = $this->tokenHandler->makeToken(...PurchaseEffectToken::make(
            $user->getId(),
            $effect->getId(),
            $ship->getId(),
            $cost,
        ));
        $purchaseToken = new PurchaseEffectToken(
            $rawToken->getJsonToken(),
            (string)$rawToken,
            TokenProvider::getActionPath(PurchaseEffectToken::class, $this->dateTimeFactory->now())
        );
        return new Transaction($cost, $purchaseToken);
    }

    private function getOffenceEffectToken(
        Effect\OffenceEffect $effect,
        UserEffect $userEffect,
        Ship $ship,
        Port $port
    ): ?UseOffenceEffectToken {
        if ($port->isSafe()) {
            // can't use any offence effects in Sanctuaries
            return null;
        }

        $token = $this->tokenHandler->makeToken(...UseOffenceEffectToken::make(
            new TokenId($userEffect->getId()),
            $userEffect->getId(),
            $ship->getId(),
            $port->getId(),
            $effect->getDamage(),
            null
        ));
        return new UseOffenceEffectToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(UseOffenceEffectToken::class, $this->dateTimeFactory->now())
        );
    }

    private function getDefenceEffectToken(UserEffect $userEffect, User $user, Ship $ship): ShipDefenceEffectToken
    {
        $token = $this->tokenHandler->makeToken(...ShipDefenceEffectToken::make(
            new TokenId($userEffect->getId()),
            $userEffect->getId(),
            $userEffect->getEffect()->getId(),
            $user->getId(),
            $ship->getId(),
            null,
            null,
            $userEffect->getEffect()->getDurationSeconds(),
            $userEffect->getEffect()->getHitCount(),
        ));
        return new ShipDefenceEffectToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(ShipDefenceEffectToken::class, $this->dateTimeFactory->now())
        );
    }

    private function getTravelEffectToken(UserEffect $userEffect, User $user, Ship $ship): ShipTravelEffectToken
    {
        $token = $this->tokenHandler->makeToken(...ShipTravelEffectToken::make(
            new TokenId($userEffect->getId()),
            $userEffect->getId(),
            $userEffect->getEffect()->getId(),
            $user->getId(),
            $ship->getId(),
            null,
            null,
            $userEffect->getEffect()->getDurationSeconds(),
            $userEffect->getEffect()->getHitCount(),
        ));
        return new ShipTravelEffectToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(ShipTravelEffectToken::class, $this->dateTimeFactory->now())
        );
    }

    /**
     * @param Ship $playingShip
     * @param Ship $victimShip
     * @param Port $currentPort
     * @param TacticalEffect[] $tacticalOptions
     * @return array
     */
    public function getOffenceOptionsAtShip(
        Ship $playingShip,
        Ship $victimShip,
        Port $currentPort,
        $tacticalOptions
    ): array {
        $availableOffenceEffects = array_filter($tacticalOptions, static function (TacticalEffect $tacticalEffect) {
            return $tacticalEffect->isAvailableShipOffence();
        });

        $offenceEffects = [];
        foreach ($availableOffenceEffects as $availableOffenceEffect) {
            $userEffect = $availableOffenceEffect->getUserEffect();
            $effect = $availableOffenceEffect->getEffect();
            if (!$userEffect || !$effect || !$effect instanceof Effect\OffenceEffect) {
                throw new \RuntimeException('An offence effect without an effect. Whaa!');
            }

            $token = $this->tokenHandler->makeToken(...UseOffenceEffectToken::make(
                new TokenId($userEffect->getId()),
                $userEffect->getId(),
                $playingShip->getId(),
                $currentPort->getId(),
                $effect->getDamage(),
                $victimShip->getId(),
            ));
            $actionToken = new UseOffenceEffectToken(
                $token->getJsonToken(),
                (string)$token,
                TokenProvider::getActionPath(UseOffenceEffectToken::class, $this->dateTimeFactory->now())
            );

            $offenceEffects[] = [
                'actionToken' => $actionToken,
                'effect' => $availableOffenceEffect->getEffect(),
                'currentCount' => $availableOffenceEffect->getCurrentCount(),
            ];
        }

        return $offenceEffects;
    }

    public function addRandomEffectsForUser(User $user): array
    {
        $effectRepo = $this->entityManager->getEffectRepo();

        $allEffectEntities = $effectRepo->getAllAboveRankThreshold(
            $user->getRank()->getThreshold(),
            Query::HYDRATE_OBJECT
        );
        $earnedEffects = \array_values(\array_filter($allEffectEntities, static function (DbEffect $effect) {
            return !empty($effect->oddsOfWinning) && \random_int(1, $effect->oddsOfWinning) === 1;
        }));

        if (empty($earnedEffects)) {
            return [];
        }

        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);
        $userEffectRepo = $this->entityManager->getUserEffectRepo();
        $mapper = $this->mapperFactory->createEffectMapper();

        return $this->entityManager->transactional(static function () use (
            $effectRepo,
            $userEntity,
            $userEffectRepo,
            $earnedEffects,
            $mapper
        ) {
            return \array_map(static function (DbEffect $effect) use (
                $effectRepo,
                $userEffectRepo,
                $userEntity,
                $mapper
            ) {
                $userEffectRepo->createNew(
                    $effectRepo->getByID($effect->id, Query::HYDRATE_OBJECT),
                    $userEntity,
                );
                return $mapper->getEffect($effectRepo->getByID($effect->id));
            }, $earnedEffects);
        });
    }

    private function getCountsPerEffect($userEffects): array
    {
        $countsOfType = [];
        foreach ($userEffects as $userEffect) {
            /** @var UserEffect $userEffect */
            $effectId = $userEffect->getEffect()->getId()->toString();
            if (!isset($countsOfType[$effectId])) {
                $countsOfType[$effectId] = 0;
            }
            $countsOfType[$effectId]++;
        }
        return $countsOfType;
    }

    private function getAll(): array
    {
        return $this->mapMany($this->entityManager->getEffectRepo()->getAll());
    }

    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createEffectMapper();
        return \array_map(static function ($result) use ($mapper) {
            return $mapper->getEffect($result);
        }, $results);
    }

    private function getOwnedEffectsForUser(User $user): array
    {
        // this method has a cache to reuse it during the same request
        $cacheKey = __METHOD__ . $user->getId()->toString();
        if (isset($this->userEffectsCache[$cacheKey])) {
            return $this->userEffectsCache[$cacheKey];
        }

        $mapper = $this->mapperFactory->createUserEffectMapper();
        $results = \array_map(static function ($result) use ($mapper) {
            return $mapper->getUserEffect($result);
        }, $this->entityManager->getUserEffectRepo()->getAllForUserId($user->getId()));
        $this->userEffectsCache[$cacheKey] = $results;
        return $results;
    }

    /**
     * @param Ship $ship
     * @return ActiveEffect[]
     */
    public function getActiveEffectsForShip(Ship $ship): array
    {
        $mapper = $this->mapperFactory->createActiveEffectMapper();
        $results = \array_map(static function ($result) use ($mapper) {
            return $mapper->getActiveEffect($result);
        }, $this->entityManager->getActiveEffectRepo()->findActiveForShipId($ship->getId()));
        return $results;
    }

    public function parseUseOffenceEffectToken(string $tokenString): UseOffenceEffectToken
    {
        return new UseOffenceEffectToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useOffenceEffectToken(UseOffenceEffectToken $applyEffectToken): void
    {
        // get all the affectedShips in the port
        $shipsInPort = $this->entityManager->getShipLocationRepo()
            ->getActiveShipsForPortId($applyEffectToken->getPortId(), Query::HYDRATE_OBJECT);

        $victimShipId = $applyEffectToken->getVictimShipId();
        if ($victimShipId) {
            $shipsInPort = find(static function (DbShipLocation $shipInPort) use ($victimShipId) {
                return $shipInPort->ship->id->equals($victimShipId);
            }, $shipsInPort);
        }

        if (empty($shipsInPort)) {
            throw new OutdatedMoveException('The target ship left before you could attack');
        }

        /** @var \App\Data\Database\Entity\UserEffect $playerEffect */
        $playerEffect = $this->entityManager->getUserEffectRepo()
            ->getByIDWithEffect($applyEffectToken->getUserEffectId(), Query::HYDRATE_OBJECT);
        $portEntity = $this->entityManager->getPortRepo()
            ->getByID($applyEffectToken->getPortId(), Query::HYDRATE_OBJECT);
        $actioningShipEntity = $this->entityManager->getShipRepo()
            ->getByID($applyEffectToken->getShipId(), Query::HYDRATE_OBJECT);

        /** @var DbShip[] $affectedShips */
        $affectedShips = \array_map(static function (DbShipLocation $shipInPort) {
            return $shipInPort->ship;
        }, ensureArray($shipsInPort));

        $this->entityManager->transactional(function () use (
            $affectedShips,
            $actioningShipEntity,
            $playerEffect,
            $portEntity,
            $applyEffectToken
        ) {
            foreach ($affectedShips as $ship) {
                /** @var DbShip $ship */
                $damage = $applyEffectToken->getDamage();
                if ($damage === -1) {
                    $damage = $ship->strength; // destroy the ship
                }
                $this->entityManager->getShipRepo()->updateStrengthValue($ship, -$damage);

                $this->entityManager->getEventRepo()
                    ->logOffence($actioningShipEntity, $portEntity, $ship, $playerEffect->effect, $damage);
            }

            $this->entityManager->getUserEffectRepo()->useEffect($playerEffect);
            $this->tokenHandler->markAsUsed($applyEffectToken->getOriginalToken());
        });
    }


    public function parseApplySimpleEffectToken(string $tokenString): GenericApplyEffectToken
    {
        return new GenericApplyEffectToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useSimpleEffectToken(GenericApplyEffectToken $applyEffectToken): void
    {
        /** @var \App\Data\Database\Entity\UserEffect $originalEffectEntity */
        $originalEffectEntity = $this->entityManager->getUserEffectRepo()->getByID(
            $applyEffectToken->getUserEffectId(),
            Query::HYDRATE_OBJECT
        );

        $triggeredByUserEntity = $this->entityManager->getUserRepo()
            ->getByID($applyEffectToken->getTriggeredById(), Query::HYDRATE_OBJECT);
        $effectEntity = $this->entityManager->getEffectRepo()
            ->getByID($applyEffectToken->getEffectId(), Query::HYDRATE_OBJECT);

        $expiry = null;
        if ($applyEffectToken->getDuration()) {
            $expiry = $this->dateTimeFactory->now()->add($applyEffectToken->getDuration());
        }

        $shipEntity = null;
        $userEntity = null;
        $portEntity = null;

        if ($applyEffectToken->getShipId()) {
            $shipEntity = $this->entityManager->getShipRepo()
                ->getByID($applyEffectToken->getShipId(), Query::HYDRATE_OBJECT);
        }
        if ($applyEffectToken->getUserId()) {
            $userEntity = $this->entityManager->getUserRepo()
                ->getByID($applyEffectToken->getUserId(), Query::HYDRATE_OBJECT);
        }
        if ($applyEffectToken->getPortId()) {
            $portEntity = $this->entityManager->getPortRepo()
                ->getByID($applyEffectToken->getPortId(), Query::HYDRATE_OBJECT);
        }

        $this->entityManager->transactional(function () use (
            $applyEffectToken,
            $originalEffectEntity,
            $effectEntity,
            $triggeredByUserEntity,
            $expiry,
            $shipEntity,
            $userEntity,
            $portEntity
        ) {
            $this->entityManager->getUserEffectRepo()->useEffect($originalEffectEntity);
            $this->entityManager->getActiveEffectRepo()->makeNew(
                $effectEntity,
                $triggeredByUserEntity,
                $applyEffectToken->getHitCount(),
                $expiry,
                $shipEntity,
                $userEntity,
                $portEntity,
            );
            $this->tokenHandler->markAsUsed($applyEffectToken->getOriginalToken());
        });
    }
}
