<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Token as TokenEntity;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\UuidInterface;

class TokenRepository extends AbstractEntityRepository
{
    public function findRefreshTokenWithUser(
        UuidInterface $tokenId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'u')
            ->join('tbl.user', 'u')
            ->where('tbl.id = :id')
            ->andWhere('tbl.type = :type')
            ->setParameter('id', $tokenId->getBytes())
            ->setParameter('type', TokenEntity::TYPE_REFRESH);

        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function isValid(
        UuidInterface $tokenId
    ): bool {
        $qb = $this->createQueryBuilder('tbl')
            ->select('count(tbl.id)')
            ->where('tbl.id = :id')
            ->andWhere('tbl.type IN (:validTypes)')
            ->setParameter('id', $tokenId->getBytes())
            ->setParameter('validTypes', TokenEntity::INVALID_TYPES);
        return !$qb->getQuery()->getSingleScalarResult();
    }

    public function markAsUsed(
        UuidInterface $tokenId,
        DateTimeImmutable $expiryTime
    ): void {
        $entity = $this->getByID($tokenId, Query::HYDRATE_OBJECT);
        if (!$entity) {
            // we can clean up used tokens once they meet their original expiry time and become invalid naturally
            $entity = new TokenEntity(
                $tokenId,
                TokenEntity::TYPE_USED,
                $this->currentTime,
                $expiryTime
            );
        }

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
