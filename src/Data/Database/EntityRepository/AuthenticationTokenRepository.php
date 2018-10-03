<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\AuthenticationToken;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class AuthenticationTokenRepository extends AbstractEntityRepository implements CleanableInterface
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
            ->setParameter('now', $this->dateTimeFactory->now());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function findAllForUserId(
        UuidInterface $userId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.user) = (:userId)')
            ->andWhere('tbl.expiry > :now')
            ->setParameter('userId', $userId->getBytes())
            ->setParameter('now', $this->dateTimeFactory->now());
        return $qb->getQuery()->getResult($resultType);
    }

    public function removeExpired(DateTimeImmutable $now): int
    {
        $sql = 'DELETE FROM ' . AuthenticationToken::class . ' t WHERE t.expiry < :now';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('now', $now);
        return $query->execute();
    }

    public function deleteById(UuidInterface $uuid, string $className = AuthenticationToken::class): void
    {
        parent::deleteById($uuid, $className);
    }

    public function clean(\DateTimeImmutable $now): int
    {
        return $this->removeExpired($now);
    }
}
