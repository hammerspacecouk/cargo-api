<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\ActiveEffect;
use DateTimeImmutable;

class ActiveEffectRepository extends AbstractEntityRepository implements CleanableInterface
{
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
