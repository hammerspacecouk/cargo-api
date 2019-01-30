<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\ActiveEffect;
use App\Data\Database\Entity\Effect as DbEffect;
use App\Data\Database\Types\EnumEffectsType;
use App\Data\TokenProvider;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\Entity\UserEffect;
use App\Domain\ValueObject\Token\Action\ApplyEffect\GenericApplyEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use DateInterval;
use Doctrine\ORM\Query;

class EffectsService extends AbstractService
{
    private $userEffectsCache = [];

    public function getShipDefenceOptions(Ship $ship, User $user): array
    {
        $userEffects = $this->getDefenceEffectsForUser($user);

        // todo - show active effects first

        $defenceOptions = \array_map(function (UserEffect $userEffect) use ($ship, $user) {
            $token = $this->tokenHandler->makeToken(...ShipDefenceEffectToken::make(
                $userEffect->getId(),
                $userEffect->getEffect()->getId(),
                $user->getId(),
                $ship->getId(),
                null,
                null,
                $userEffect->getEffect()->getDurationSeconds(),
                $userEffect->getEffect()->getHitCount(),
            ));
            return [
                'actionToken' => new ShipDefenceEffectToken(
                    $token->getJsonToken(),
                    (string)$token,
                    TokenProvider::getActionPath(ShipDefenceEffectToken::class, $this->dateTimeFactory->now())
                ),
                'effect' => $userEffect->getEffect(),
                'hitsRemaining' => null,
                'timeRemaining' => null,
            ];
        }, $userEffects);

        return $defenceOptions;
    }

    public function getDefenceEffectsForUser(User $user): array
    {
        return $this->getEffectsOfTypeForUser($user, EnumEffectsType::TYPE_DEFENCE);
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
}
