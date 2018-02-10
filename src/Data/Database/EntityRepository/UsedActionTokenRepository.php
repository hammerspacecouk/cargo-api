<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\UsedActionToken;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\UuidInterface;

class UsedActionTokenRepository extends AbstractEntityRepository
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
            $tokenId,
            $expiryTime
        );
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function removeExpired(DateTimeImmutable $now): void
    {
        $sql = 'DELETE FROM ' . Token::class . ' t WHERE t.expiry < :now';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('now', $now);
        $query->execute();
    }
}
