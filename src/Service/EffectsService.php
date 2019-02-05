<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\ActiveEffect;
use App\Data\Database\Entity\Effect as DbEffect;
use App\Data\Database\Types\EnumEffectsType;
use App\Data\TokenProvider;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\Entity\UserEffect;
use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipTravelEffectToken;
use function App\Functions\Arrays\find;
use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;

class EffectsService extends AbstractService
{
    private $userEffectsCache = [];

    public function getShipDefenceOptions(Ship $ship, User $user): array
    {
        $allEffects = $this->entityManager->getEffectRepo()->getTypeAboveRankThreshold(
            EnumEffectsType::TYPE_DEFENCE,
            $user->getRank()->getThreshold()
        );
        $mapper = $this->mapperFactory->createEffectMapper();
        $allEffects = \array_map(function ($result) use ($mapper) {
            return $mapper->getEffect($result);
        }, $allEffects);

        $userEffects = $this->getDefenceEffectsForUser($user);
        /** @var ActiveEffect[] $activeShipEffects */
        $activeShipEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId(
            $ship->getId(),
            EnumEffectsType::TYPE_DEFENCE,
            Query::HYDRATE_OBJECT
        );

        $defenceOptions = \array_map(function (Effect $effect) use ($ship, $user, $activeShipEffects, $userEffects) {
            $actionToken = null;
            $hitsRemaining = null;
            $expiry = null;

            /** @var ActiveEffect|null $activeEffect */
            $activeEffect = find(function (ActiveEffect $activeEffect) use ($effect) {
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
        $allEffects = $this->entityManager->getEffectRepo()->getTypeAboveRankThreshold(
            EnumEffectsType::TYPE_TRAVEL,
            $user->getRank()->getThreshold()
        );
        $mapper = $this->mapperFactory->createEffectMapper();
        $allEffects = \array_map(function ($result) use ($mapper) {
            return $mapper->getEffect($result);
        }, $allEffects);

        /** @var ActiveEffect[] $activeShipEffects */
        $activeTravelEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId(
            $ship->getId(),
            EnumEffectsType::TYPE_TRAVEL,
        );

        if (!empty($activeTravelEffects)) {
            return \array_map(function(array $activeEffect) use ($mapper) {
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
                    $this->uuidFactory->uuid5(Uuid::NIL, (string)$userEffect->getId()), // todo - all options should share the same key. prevent multiple tabs
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
        return $this->getEffectsOfTypeForUser($user, EnumEffectsType::TYPE_DEFENCE);
    }

    public function getTravelEffectsForUser(User $user): array
    {
        return $this->getEffectsOfTypeForUser($user, EnumEffectsType::TYPE_TRAVEL);
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

    private function getEffectsOfTypeForUser(User $user, string $type): array
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

    public function getApplicableTravelEffectForShip(Ship $ship): ?Effect
    {
        /** @var ActiveEffect[] $activeShipEffects */
        $activeTravelEffects = $this->entityManager->getActiveEffectRepo()->findActiveForShipId(
            $ship->getId(),
            EnumEffectsType::TYPE_TRAVEL,
        );
        if (empty($activeTravelEffects)) {
            return null;
        }
        // there should never be more than one, but just in case, we'll apply only the first one we find
        $activeEffect = reset($activeTravelEffects);
        return $this->mapperFactory->createEffectMapper()->getEffect($activeEffect['effect']);
    }
}
