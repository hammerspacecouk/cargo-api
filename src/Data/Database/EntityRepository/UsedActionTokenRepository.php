<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\UsedActionToken;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class UsedActionTokenRepository extends AbstractEntityRepository implements CleanableInterface
{
    public function hasBeenUsed(
        UuidInterface $tokenId
    ): bool {
        $qb = $this->createQueryBuilder('tbl')
            ->select('count(tbl.id)')
            ->where('tbl.id = :id')
            ->setParameter('id', $tokenId->getBytes());
        return !!$qb->getQuery()->getSingleScalarResult();
    }

    public function markAsUsed(
        UuidInterface $tokenId,
        DateTimeImmutable $expiryTime
    ): void {
        $entity = new UsedActionToken(
            $expiryTime
        );
        $entity->id = $tokenId;
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function removeExpired(DateTimeImmutable $now): int
    {
        $sql = 'DELETE FROM ' . UsedActionToken::class . ' t WHERE t.expiry < :now';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('now', $now);
        return $query->execute();
    }

    public function clean(\DateTimeImmutable $now): int
    {
        return $this->removeExpired($now);
    }
}
