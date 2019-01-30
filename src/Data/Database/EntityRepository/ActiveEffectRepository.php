<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\ActiveEffect;
use App\Data\Database\Entity\Effect;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\User;
use DateTimeImmutable;

class ActiveEffectRepository extends AbstractEntityRepository implements CleanableInterface
{
    public function makeNew(
        Effect $effect,
        User $triggeredByUser,
        ?int $remainingCount,
        ?DateTimeImmutable $expiry,
        ?Ship $ship = null,
        ?User $user = null,
        ?Port $port = null
    ): ActiveEffect {
        $activeEffect = new ActiveEffect(
            $effect,
            $remainingCount,
            $expiry,
            $triggeredByUser,
        );
        $activeEffect->appliesToPort = $port;
        $activeEffect->appliesToShip = $ship;
        $activeEffect->appliesToUser = $user;

        // todo - log it!

        $this->getEntityManager()->persist($activeEffect);
        $this->getEntityManager()->flush();
        return $activeEffect;
    }

    public function removeExpired(DateTimeImmutable $now): int
    {
        $sql = 'DELETE FROM ' . ActiveEffect::class . ' t WHERE t.expiry < :now';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('now', $now);
        return $query->execute();
    }

    public function clean(DateTimeImmutable $now): int
    {
        return $this->removeExpired($now);
    }
}
