<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\AuthenticationToken;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class AuthenticationTokenRepository extends AbstractEntityRepository
{
    public function findUnexpiredById(
        UuidInterface $tokenId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'u')
            ->join('tbl.user', 'u')
            ->where('tbl.id = :id')
            ->andWhere('tbl.expiry > :now')
            ->setParameter('id', $tokenId->getBytes())
            ->setParameter('now', $this->currentTime)
        ;
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function removeExpired(DateTimeImmutable $now): void
    {
        $sql = 'DELETE FROM ' . AuthenticationToken::class . ' t WHERE t.expiry < :now';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('now', $now);
        $query->execute();
    }
}
