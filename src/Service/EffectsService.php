<?php
declare(strict_types=1);

namespace App\Service;

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
use App\Domain\ValueObject\LockedTransaction;
use App\Domain\ValueObject\TacticalEffect;
use App\Domain\ValueObject\Token\Action\ApplyEffect\BlockadeEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipTravelEffectToken;
use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use App\Domain\ValueObject\Token\Action\UseOffenceEffectToken;
use App\Domain\ValueObject\Token\Action\UseWormholeEffectToken;
use App\Domain\ValueObject\TokenId;
use App\Domain\ValueObject\Transaction;
use App\Infrastructure\DateTimeFactory;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use function App\Functions\Arrays\ensureArray;
use function App\Functions\Arrays\filteredMap;
use function App\Functions\Arrays\find;

class EffectsService extends AbstractService
{
    /**
     * @var array<string, mixed>
     */
    private array $userEffectsCache = [];

    /**
     * @param Ship $ship
     * @param User $user
     * @param Port $currentPort
     * @return Transaction[]
     */
    public function getEffectsForLocation(Ship $ship, User $user, Port $currentPort): array
    {
        $allEffects = $this->getAllPurchasable(); //future todo - effects to buy vary by location
        return array_map(function (Effect $effect) use ($ship, $user, $currentPort) {
            if (!$user->getRank()->meets($effect->getMinimumRank())) {
                return new LockedTransaction($effect->getMinimumRank());
            }
            return $this->getPurchaseEffectTransaction($user, $effect, $ship, $currentPort);
        }, $allEffects);
    }

    public function getActiveTravelEffectsForShip(Ship $ship): array
    {
        $activeShipEffects = $this->getActiveEffectsForShip($ship);
        return filteredMap($activeShipEffects, static function (ActiveEffect $activeEffect) {
            $id = $activeEffect->getEffect()->getId()->toString();
            if (!$activeEffect->getEffect() instanceof Effect\TravelEffect) {
                return null;
            }

            return new TacticalEffect(
                $activeEffect->getEffect(),
                true,
                null,
                $activeEffect,
                false,
                null,
                $countsOfType[$id] ?? 0,
                $activeEffect->getRemainingCount(),
                $activeEffect->getExpiry()
            );
        });
    }

    public function getUserWormholeActions(User $user, Ship $ship, Port $ignore): array
    {
        $wormholeEffects = $this->entityManager->getUserEffectRepo()->getAllOfEffectForUserId(
            $user->getId(),
            Uuid::fromString('ab5a97d4-12ae-4c7b-8f95-102ee01aa74c')
        );

        if (empty($wormholeEffects)) {
            return [];
        }
        $userEffectId = $wormholeEffects[0]['id'];

        $mapper = $this->mapperFactory->createPortMapper();
        /** @var Port[] $availablePorts */
        $availablePorts = array_map(static function ($result) use ($mapper) {
            return $mapper->getPort($result['port']);
        }, $this->entityManager->getPortVisitRepo()->getAllSafeForPlayerId($user->getId()));

        $actions = [];
        foreach ($availablePorts as $port) {
            if ($port->equals($ignore)) {
                continue;
            }

            $token = $this->tokenHandler->makeToken(...UseWormholeEffectToken::make(
                new TokenId($userEffectId),
                $userEffectId,
                $ship->getId(),
                $port->getId(),
            ));

            $actions[] = [
                'port' => $port,
                'actionToken' => new UseWormholeEffectToken(
                    $token,
                    TokenProvider::getActionPath(UseWormholeEffectToken::class)
                ),
            ];
        }

        return $actions;
    }

    /**
     * @param Ship $ship
     * @param User $user
     * @param ShipLocation $shipLocation
     * @return TacticalEffect[]
     */
    public function getUserEffectsForLocation(Ship $ship, User $user, ShipLocation $shipLocation): array
    {
        $isInPort = $shipLocation instanceof ShipInPort;
        $isInChannel = $shipLocation instanceof ShipInChannel;

        $userEffects = $this->getOwnedEffectsForUser($user);
        $countsOfType = $this->getCountsPerEffect($userEffects);

        $activeShipEffects = $this->getActiveEffectsForShip($ship);

        $availableEffects = [];
        $seenEffects = []; // for de-duping

        // add the active effects first
        foreach ($activeShipEffects as $activeEffect) {
            $id = $activeEffect->getEffect()->getId()->toString();
            if (array_key_exists($id, $seenEffects)) {
                continue; // only need one of each type of effect
            }

            $availableEffects[] = new TacticalEffect(
                $activeEffect->getEffect(),
                true,
                null,
                $activeEffect,
                false,
                null,
                $countsOfType[$id] ?? 0,
                $activeEffect->getRemainingCount(),
                $activeEffect->getExpiry()
            );
            $seenEffects[$id] = true;
        }

        // add the rest of the effects (if not already active)
        foreach ($userEffects as $userEffect) {
            $effect = $userEffect->getEffect();
            $id = $effect->getId()->toString();
            if (array_key_exists($id, $seenEffects)) {
                continue; // only need one of each type of effect
            }
            $availableEffects[] = $this->makeTacticalEffect(
                $userEffect,
                $user,
                $ship,
                $shipLocation,
                $countsOfType,
                ($isInPort && $effect->canBeUsedInPort()) || ($isInChannel && $effect->canBeUsedInChannel())
            );
            $seenEffects[$id] = true;
        }

        // order them correctly
        usort($availableEffects, static function (TacticalEffect $a, TacticalEffect $b) {
            return $a->getEffect()->sortCompare($b->getEffect());
        });
        return $availableEffects;
    }

    /**
     * @param UserEffect $userEffect
     * @param User $user
     * @param Ship $ship
     * @param ShipLocation $shipLocation
     * @param array<string, int> $countsOfType
     * @param bool $canBeUsedHere
     * @return TacticalEffect
     */
    private function makeTacticalEffect(
        UserEffect $userEffect,
        User $user,
        Ship $ship,
        ShipLocation $shipLocation,
        array $countsOfType,
        bool $canBeUsedHere
    ): TacticalEffect {
        $effect = $userEffect->getEffect();

        $actionToken = null;
        $hitsRemaining = null;
        $expiry = null;
        $shipSelect = false;
        $purchaseToken = null;
        $specialLabel = null;

        // if it's in active effects. populate hitsRemaining or expiry
        if ($canBeUsedHere &&
            $shipLocation instanceof ShipInPort &&
            $effect instanceof Effect\OffenceEffect &&
            $effect->affectsAllShips() &&
            $ship->canUseOffence()
        ) {
            $actionToken = $this->getOffenceEffectToken(
                $effect,
                $userEffect,
                $ship,
                $shipLocation->getPort(),
                $user->getMarket()->getMilitaryMultiplier(),
            );
        } elseif ($canBeUsedHere && $effect instanceof Effect\DefenceEffect) {
            $actionToken = $this->getDefenceEffectToken($userEffect, $user, $ship);
        } elseif ($canBeUsedHere && $effect instanceof Effect\TravelEffect && !$ship->isProbe()) {
            $actionToken = $this->getTravelEffectToken($userEffect, $user, $ship);
        } elseif ($canBeUsedHere && $effect instanceof Effect\BlockadeEffect &&
            $shipLocation instanceof ShipInPort &&
            $ship->canUseOffence() &&
            !$shipLocation->getPort()->isBlockaded() &&
            !$shipLocation->getPort()->isSafe()
        ) {
            $actionToken = $this->getBlockadeEffectToken($userEffect, $user, $shipLocation->getPort(), $ship);
        } elseif ($canBeUsedHere && $effect instanceof Effect\SpecialEffect) {
            $specialLabel = $effect->getLabel();
        }

        if ($effect instanceof Effect\OffenceEffect && !$effect->affectsAllShips()) {
            $shipSelect = true; // no actionToken
        }

        return new TacticalEffect(
            $effect,
            false,
            $userEffect,
            null,
            $shipSelect,
            $specialLabel,
            $countsOfType[$effect->getId()->toString()] ?? 0,
            $hitsRemaining,
            $expiry,
            $actionToken,
        );
    }

    private function getPurchaseEffectTransaction(
        User $user,
        Effect $effect,
        Ship $ship,
        Port $port
    ): Transaction {
        $cost = $effect->getPurchaseCost();
        $cost *= $user->getMarket()->getEconomyMultiplier();
        if ($port->isSafe()) {
            // prices are more expensive in safe places
            $cost *= 2.5;
        }
        $cost = (int)round($cost);

        $rawToken = $this->tokenHandler->makeToken(...PurchaseEffectToken::make(
            $user->getId(),
            $effect->getId(),
            $ship->getId(),
            $cost,
        ));
        $purchaseToken = new PurchaseEffectToken(
            $rawToken,
            TokenProvider::getActionPath(PurchaseEffectToken::class)
        );
        return new Transaction($cost, $purchaseToken, 0, $effect);
    }

    private function getOffenceEffectToken(
        Effect\OffenceEffect $effect,
        UserEffect $userEffect,
        Ship $ship,
        Port $port,
        float $militaryMultiplier
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
            $effect->getDamage($militaryMultiplier),
            null
        ));
        return new UseOffenceEffectToken(
            $token,
            TokenProvider::getActionPath(UseOffenceEffectToken::class)
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
            $token,
            TokenProvider::getActionPath(ShipDefenceEffectToken::class)
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
            $token,
            TokenProvider::getActionPath(ShipTravelEffectToken::class)
        );
    }

    private function getBlockadeEffectToken(
        UserEffect $userEffect,
        User $user,
        Port $port,
        Ship $ship
    ): BlockadeEffectToken {
        $token = $this->tokenHandler->makeToken(...BlockadeEffectToken::make(
            new TokenId($userEffect->getId()),
            $userEffect->getId(),
            $userEffect->getEffect()->getId(),
            $user->getId(),
            $ship->getId(),
            $port->getId(),
            null,
            $userEffect->getEffect()->getDurationSeconds(),
        ));
        return new BlockadeEffectToken(
            $token,
            TokenProvider::getActionPath(BlockadeEffectToken::class)
        );
    }

    /**
     * @param Ship $playingShip
     * @param Ship $victimShip
     * @param Port $currentPort
     * @param TacticalEffect[] $tacticalOptions
     * @return array[]
     */
    public function getOffenceOptionsAtShip(
        Ship $playingShip,
        Ship $victimShip,
        Port $currentPort,
        array $tacticalOptions,
        float $militaryMultiplier
    ): array {
        $availableOffenceEffects = array_filter($tacticalOptions, static function (TacticalEffect $tacticalEffect) {
            return $tacticalEffect->isAvailableShipOffence();
        });

        $offenceEffects = [];
        foreach ($availableOffenceEffects as $availableOffenceEffect) {
            $userEffect = $availableOffenceEffect->getUserEffect();
            $effect = $availableOffenceEffect->getEffect();
            if (!$userEffect || !$effect instanceof Effect\OffenceEffect) {
                throw new \RuntimeException('An offence effect without an effect. Whaa!');
            }

            $token = $this->tokenHandler->makeToken(...UseOffenceEffectToken::make(
                new TokenId($userEffect->getId()),
                $userEffect->getId(),
                $playingShip->getId(),
                $currentPort->getId(),
                $effect->getDamage($militaryMultiplier),
                $victimShip->getId(),
            ));
            $actionToken = new UseOffenceEffectToken(
                $token,
                TokenProvider::getActionPath(UseOffenceEffectToken::class)
            );

            $offenceEffects[] = [
                'actionToken' => $actionToken,
                'effect' => $availableOffenceEffect->getEffect(),
                'currentCount' => $availableOffenceEffect->getCurrentCount(),
            ];
        }

        return $offenceEffects;
    }

    /**
     * @param User $user
     * @return Effect[]
     */
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

    /**
     * @param UserEffect[] $userEffects
     * @return array<string, int>
     */
    private function getCountsPerEffect(array $userEffects): array
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

    /**
     * @return Effect[]
     */
    private function getAllPurchasable(): array
    {
        return $this->mapMany($this->entityManager->getEffectRepo()->getAllPurchasable());
    }

    /**
     * @param array []$results
     * @return Effect[]
     */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createEffectMapper();
        return \array_map(static function ($result) use ($mapper) {
            return $mapper->getEffect($result);
        }, $results);
    }

    /**
     * @param User $user
     * @return UserEffect[]
     */
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
        return \array_map(static function ($result) use ($mapper) {
            return $mapper->getActiveEffect($result);
        }, $this->entityManager->getActiveEffectRepo()->findActiveForShipId($ship->getId()));
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
                $victimUserId = $ship->owner->id;
                /** @var DbShip $ship */
                $damage = $applyEffectToken->getDamage();
                if ($damage === -1) {
                    $damage = $ship->strength; // destroy the ship
                }
                if ($damage >= $ship->strength) {
                    $this->entityManager->getUserAchievementRepo()->recordShipDestroyed($victimUserId);
                    $this->entityManager->getUserAchievementRepo()->recordDestroyedShip($playerEffect->user->id);
                }
                $this->entityManager->getEventRepo()
                    ->logOffence($actioningShipEntity, $portEntity, $ship, $playerEffect->effect, $damage);
                $this->entityManager->getShipRepo()->updateStrengthValue($ship, (int)-$damage);
                $this->entityManager->getUserAchievementRepo()->recordShipAttacked($victimUserId);
                if ($ship->shipClass->isHospitalShip) {
                    $this->entityManager->getUserAchievementRepo()->recordAttackHospitalShip($playerEffect->user->id);
                }
            }

            $this->entityManager->getUserAchievementRepo()->recordAttackedShip($playerEffect->user->id);
            $this->entityManager->getUserEffectRepo()->useEffect($playerEffect);
            $this->tokenHandler->markAsUsed($applyEffectToken->getOriginalToken());
        });
    }


    public function parseApplySimpleEffectToken(string $tokenString): GenericApplyEffectToken
    {
        return new GenericApplyEffectToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useBlockadeToken(GenericApplyEffectToken $applyEffectToken): void
    {
        /** @var \App\Data\Database\Entity\UserEffect $originalEffectEntity */
        $originalEffectEntity = $this->entityManager->getUserEffectRepo()->getByID(
            $applyEffectToken->getUserEffectId(),
            Query::HYDRATE_OBJECT
        );

        if (!$applyEffectToken->getPortId()) {
            return;
        }

        $triggeredByUserEntity = $this->entityManager->getUserRepo()
            ->getByID($applyEffectToken->getTriggeredById(), Query::HYDRATE_OBJECT);
        $portEntity = $this->entityManager->getPortRepo()
            ->getByID($applyEffectToken->getPortId(), Query::HYDRATE_OBJECT);

        $portEntity->blockadedBy = $triggeredByUserEntity;
        $portEntity->blockadedUntil = DateTimeFactory::now()->add($applyEffectToken->getDuration());

        $this->entityManager->persist($portEntity);
        $this->entityManager->flush();

        $this->entityManager->transactional(function () use (
            $applyEffectToken,
            $originalEffectEntity,
            $portEntity,
            $triggeredByUserEntity
        ) {
            $this->entityManager->persist($portEntity);
            $this->entityManager->getUserAchievementRepo()->recordBlockade($applyEffectToken->getTriggeredById());
            $this->entityManager->getEventRepo()->logBlockade($triggeredByUserEntity, $portEntity);
            $this->entityManager->getUserEffectRepo()->useEffect($originalEffectEntity);
            $this->tokenHandler->markAsUsed($applyEffectToken->getOriginalToken());
            $this->entityManager->flush();
        });
    }

    public function useSimpleEffectToken(
        GenericApplyEffectToken $applyEffectToken,
        bool $isDefence,
        bool $isTravel
    ): void {
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
            $expiry = DateTimeFactory::now()->add($applyEffectToken->getDuration());
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
            $portEntity,
            $isDefence,
            $isTravel
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

            if ($isDefence) {
                $this->entityManager->getUserAchievementRepo()->recordDefenceEffect($triggeredByUserEntity->id);
            }

            if ($isTravel) {
                $this->entityManager->getUserAchievementRepo()->recordTravelEffect($triggeredByUserEntity->id);
            }

            $this->tokenHandler->markAsUsed($applyEffectToken->getOriginalToken());
        });
    }

    public function parseUseWormholeToken(string $tokenString): UseWormholeEffectToken
    {
        return new UseWormholeEffectToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useWormholeEffectToken(UseWormholeEffectToken $applyEffectToken): void
    {
        /** @var \App\Data\Database\Entity\UserEffect $playerEffect */
        $playerEffect = $this->entityManager->getUserEffectRepo()
            ->getByIDWithEffect($applyEffectToken->getUserEffectId(), Query::HYDRATE_OBJECT);
        $destinationPortEntity = $this->entityManager->getPortRepo()
            ->getByID($applyEffectToken->getDestinationId(), Query::HYDRATE_OBJECT);
        /** @var DbShip $actioningShipEntity */
        $actioningShipEntity = $this->entityManager->getShipRepo()
            ->getByID($applyEffectToken->getShipId(), Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function () use (
            $actioningShipEntity,
            $playerEffect,
            $destinationPortEntity,
            $applyEffectToken
        ) {
            $this->entityManager->getShipRepo()->leaveConvoy($actioningShipEntity->id);
            $this->entityManager->getShipLocationRepo()->exitLocation($actioningShipEntity);
            $this->entityManager->getShipLocationRepo()->makeInPort($actioningShipEntity, $destinationPortEntity);
            $this->entityManager->getUserEffectRepo()->useEffect($playerEffect);
            $this->tokenHandler->markAsUsed($applyEffectToken->getOriginalToken());
        });
    }
}
