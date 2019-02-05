<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\ActiveEffect;
use App\Data\Database\Entity\Effect;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

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
        if (!$expiry) {
            // everything will expire eventually. use it or lose it
            $expiry = $this->dateTimeFactory->now()->add(new DateInterval('P3M'));
        }

        $activeEffect = new ActiveEffect(
            $effect,
            $remainingCount,
            $expiry,
            $triggeredByUser,
        );
        $activeEffect->appliesToPort = $port;
        $activeEffect->appliesToShip = $ship;
        $activeEffect->appliesToUser = $user;

        $this->getEntityManager()->getEventRepo()->logUseEffect(
            $effect,
            $triggeredByUser,
            $ship,
            $port,
        );

        $this->getEntityManager()->persist($activeEffect);
        $this->getEntityManager()->flush();
        return $activeEffect;
    }

    public function findActiveForShipId(
        UuidInterface $shipId,
        ?string $effectType = null,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'e')
            ->join('tbl.effect', 'e')
            ->where('IDENTITY(tbl.appliesToShip) = (:shipId)')
            ->andWhere('tbl.expiry > :now')
            ->setParameter('shipId', $shipId->getBytes())
            ->setParameter('now', $this->dateTimeFactory->now());

        if ($effectType) {
            $qb = $qb->andWhere('e.type = :type')
                ->setParameter('type', $effectType);
        }

        return $qb->getQuery()->getResult($resultType);
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
