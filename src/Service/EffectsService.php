<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\ActiveEffect as DbActiveEffect;
use App\Data\Database\Entity\Effect as DbEffect;
use App\Data\Database\Entity\ShipLocation;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Types\EnumEffectsType;
use App\Data\TokenProvider;
use App\Domain\Entity\ActiveEffect;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\Entity\UserEffect;
use App\Domain\Exception\OutdatedMoveException;
use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipTravelEffectToken;
use App\Domain\ValueObject\Token\Action\UseOffenceEffectToken;
use function App\Functions\Arrays\ensureArray;
use function App\Functions\Arrays\find;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;

class EffectsService extends AbstractService
{
    private $userEffectsCache = [];

    public function getOffenceOptionsAtShip(Ship $playingShip, Ship $victimShip, Port $currentPort): array
    {
        $user = $playingShip->getOwner();

        $allEffects = $this->getAvailableEffectsOfTypeForUser($user, EnumEffectsType::TYPE_OFFENCE);
        $userEffects = $this->getOffenceEffectsForUser($user);

        return \array_map(function (Effect $effect) use ($playingShip, $userEffects, $victimShip, $currentPort) {

            /** @var UserEffect|null $userEffect */
            $userEffect = find(function (UserEffect $userEffect) use ($effect) {
                return $effect->getId()->equals($userEffect->getEffect()->getId());
            }, $userEffects);

            $actionToken = null;
            if ($userEffect) {
                /** @var Effect\OffenceEffect $effect */
                $effect = $userEffect->getEffect();

                // else if it's in userEffects, populate the action token
                $token = $this->tokenHandler->makeToken(...UseOffenceEffectToken::make(
                    $this->uuidFactory->uuid5(Uuid::NIL, (string)$userEffect->getId()), // todo - dedupe?
                    $userEffect->getId(),
                    $playingShip->getId(),
                    $currentPort->getId(),
                    $effect->getDamage(),
                    $effect->affectsAllShips() ? null : $victimShip->getId(),
                    ));
                $actionToken = new UseOffenceEffectToken(
                    $token->getJsonToken(),
                    (string)$token,
                    TokenProvider::getActionPath(UseOffenceEffectToken::class, $this->dateTimeFactory->now())
                );
            }

            return [
                'actionToken' => $actionToken,
                'effect' => $effect,
            ];

        }, $allEffects);
    }

    public function getShipDefenceOptions(Ship $ship, User $user): array
    {
        $allEffects = $this->getAvailableEffectsOfTypeForUser($user, EnumEffectsType::TYPE_DEFENCE);

        $userEffects = $this->getDefenceEffectsForUser($user);
        /** @var DbActiveEffect[] $activeShipEffects */
        $activeShipEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId(
            $ship->getId(),
            EnumEffectsType::TYPE_DEFENCE,
            Query::HYDRATE_OBJECT
        );

        $defenceOptions = \array_map(function (Effect $effect) use ($ship, $user, $activeShipEffects, $userEffects) {
            $actionToken = null;
            $hitsRemaining = null;
            $expiry = null;

            /** @var DbActiveEffect|null $activeEffect */
            $activeEffect = find(function (DbActiveEffect $activeEffect) use ($effect) {
                return $effect->getId()->equals($activeEffect->effect->id);
            }, $activeShipEffects);


            /** @var UserEffect|null $userEffect */
            $userEffect = find(function (UserEffect $userEffect) use ($effect) {
                return $effect->getId()->equals($userEffect->getEffect()->getId());
            }, $userEffects);

            // if it's in active effects. populate hitsRemaining or expiry
            if ($activeEffect) {
                $hitsRemaining = $activeEffect->remainingCount;
                $expiry = $activeEffect->expiry ? $activeEffect->expiry->format('c') : null;
            } elseif ($userEffect) {
                // else if it's in userEffects, populate the action token
                $token = $this->tokenHandler->makeToken(...ShipDefenceEffectToken::make(
                    $this->uuidFactory->uuid5(Uuid::NIL, (string)$userEffect->getId()),
                    $userEffect->getId(),
                    $userEffect->getEffect()->getId(),
                    $user->getId(),
                    $ship->getId(),
                    null,
                    null,
                    $userEffect->getEffect()->getDurationSeconds(),
                    $userEffect->getEffect()->getHitCount(),
                    ));
                $actionToken = new ShipDefenceEffectToken(
                    $token->getJsonToken(),
                    (string)$token,
                    TokenProvider::getActionPath(ShipDefenceEffectToken::class, $this->dateTimeFactory->now())
                );
            }

            return [
                'actionToken' => $actionToken,
                'effect' => $effect,
                'hitsRemaining' => $hitsRemaining,
                'expiry' => $expiry,
            ];
        }, $allEffects);

        return $defenceOptions;
    }

    public function getShipTravelOptions(Ship $ship, User $user): array
    {
        $allEffects = $this->getAvailableEffectsOfTypeForUser($user, EnumEffectsType::TYPE_TRAVEL);
        $mapper = $this->mapperFactory->createEffectMapper();

        /** @var DbActiveEffect[] $activeShipEffects */
        $activeTravelEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId(
            $ship->getId(),
            EnumEffectsType::TYPE_TRAVEL,
            );

        if (!empty($activeTravelEffects)) {
            return \array_map(function (array $activeEffect) use ($mapper) {
                return [
                    'isActive' => true,
                    'effect' => $mapper->getEffect($activeEffect['effect']),
                    'actionToken' => null,
                ];
            }, $activeTravelEffects);
        }

        $userEffects = $this->getTravelEffectsForUser($user);

        $travelOptions = \array_map(function (Effect $effect) use ($ship, $user, $userEffects) {
            $actionToken = null;

            /** @var UserEffect|null $userEffect */
            $userEffect = find(function (UserEffect $userEffect) use ($effect) {
                return $effect->getId()->equals($userEffect->getEffect()->getId());
            }, $userEffects);

            // if it's in userEffects, populate the action token
            if ($userEffect) {
                $token = $this->tokenHandler->makeToken(...ShipTravelEffectToken::make(
                    $this->uuidFactory->uuid5(Uuid::NIL, (string)$userEffect->getId()),
                    // todo - all options should share the same key. prevent multiple tabs
                    $userEffect->getId(),
                    $userEffect->getEffect()->getId(),
                    $user->getId(),
                    $ship->getId(),
                    null,
                    null,
                    ));
                $actionToken = new ShipTravelEffectToken(
                    $token->getJsonToken(),
                    (string)$token,
                    TokenProvider::getActionPath(ShipTravelEffectToken::class, $this->dateTimeFactory->now())
                );
            }

            return [
                'actionToken' => $actionToken,
                'isActive' => false,
                'effect' => $effect,
            ];
        }, $allEffects);

        return $travelOptions;
    }

    public function getDefenceEffectsForUser(User $user): array
    {
        return $this->getUserEffectsOfTypeForUser($user, EnumEffectsType::TYPE_DEFENCE);
    }

    public function getTravelEffectsForUser(User $user): array
    {
        return $this->getUserEffectsOfTypeForUser($user, EnumEffectsType::TYPE_TRAVEL);
    }

    public function getOffenceEffectsForUser(User $user): array
    {
        return $this->getUserEffectsOfTypeForUser($user, EnumEffectsType::TYPE_OFFENCE);
    }

    public function addRandomEffectsForUser(User $user): array
    {
        $effectRepo = $this->entityManager->getEffectRepo();

        $allEffectEntities = $effectRepo->getAllAboveRankThreshold(
            $user->getRank()->getThreshold(),
            Query::HYDRATE_OBJECT
        );
        $earnedEffects = \array_values(\array_filter($allEffectEntities, function (DbEffect $effect) {
            return !empty($effect->oddsOfWinning) && \random_int(1, $effect->oddsOfWinning) === 1;
        }));

        if (empty($earnedEffects)) {
            return [];
        }

        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);
        $userEffectRepo = $this->entityManager->getUserEffectRepo();
        $mapper = $this->mapperFactory->createEffectMapper();

        return $this->entityManager->transactional(function () use (
            $effectRepo,
            $userEntity,
            $userEffectRepo,
            $earnedEffects,
            $mapper
        ) {
            return \array_map(function (DbEffect $effect) use ($effectRepo, $userEffectRepo, $userEntity, $mapper) {
                $userEffectRepo->createNew(
                    $effectRepo->getByID($effect->id, Query::HYDRATE_OBJECT),
                    $userEntity,
                    );
                return $mapper->getEffect($effectRepo->getByID($effect->id));
            }, $earnedEffects);
        });
    }

    private function getAvailableEffectsOfTypeForUser(User $user, string $type): array
    {
        $allEffects = $this->entityManager->getEffectRepo()->getTypeAboveRankThreshold(
            $type,
            $user->getRank()->getThreshold()
        );
        $mapper = $this->mapperFactory->createEffectMapper();
        return \array_map(function ($result) use ($mapper) {
            return $mapper->getEffect($result);
        }, $allEffects);
    }

    private function getUserEffectsOfTypeForUser(User $user, string $type): array
    {
        // this method has a cache to reuse it during the same request
        $cacheKey = __CLASS__ . __METHOD__ . $user->getId()->toString() . $type;
        if (isset($this->userEffectsCache[$cacheKey])) {
            return $this->userEffectsCache[$cacheKey];
        }

        $mapper = $this->mapperFactory->createUserEffectMapper();
        $results = \array_map(function ($result) use ($mapper) {
            return $mapper->getUserEffect($result);
        }, $this->entityManager->getUserEffectRepo()->getUniqueOfTypeForUserId($user->getId(), $type));
        $this->userEffectsCache[$cacheKey] = $results;
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
            $shipsInPort = find(function (ShipLocation $shipInPort) use ($victimShipId) {
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

        /** @var DbShip $affectedShips */
        $affectedShips = \array_map(function (ShipLocation $shipInPort) {
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

    public function getActiveEffectsForShip(Ship $ship): array
    {
        $activeEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId($ship->getId());

        $mapper = $this->mapperFactory->createEffectMapper();
        return \array_map(function (array $activeEffect) use ($mapper) {
            return $mapper->getEffect($activeEffect['effect']);
        }, $activeEffects);
    }

    public function getApplicableTravelEffectForShip(Ship $ship): ?ActiveEffect
    {
        /** @var DbActiveEffect[] $activeShipEffects */
        $activeTravelEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId(
            $ship->getId(),
            EnumEffectsType::TYPE_TRAVEL,
            );
        if (empty($activeTravelEffects)) {
            return null;
        }
        // there should never be more than one, but just in case, we'll apply only the first one we find
        $activeEffect = reset($activeTravelEffects);
        return new ActiveEffect(
            $activeEffect['id'],
            $this->mapperFactory->createEffectMapper()->getEffect($activeEffect['effect']),
            );
    }
}
