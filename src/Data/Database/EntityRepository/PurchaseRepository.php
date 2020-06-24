<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PurchaseRepository extends AbstractEntityRepository
{
    /**
     * @param UuidInterface $userId
     * @param int $resultType
     * @return mixed
     */
    public function getByID(
        UuidInterface $userId,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'user')
            ->join('tbl.user', 'user')
            ->where('tbl.id = :id')
            ->setParameter('id', $userId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function purchaseExistsForUserId(string $purchaseId, UuidInterface $userId): bool
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('COUNT(1)')
            ->where('IDENTITY(tbl.user) = :userId')
            ->andWhere('tbl.checkoutSessionId = :purchaseId')
            ->setParameter('purchaseId', $purchaseId)
            ->setParameter('userId', $userId->getBytes());
        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getAllForUserId(UuidInterface $userId): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.user) = :id')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setParameter('id', $userId->getBytes());
        return $qb->getQuery()->getArrayResult();
    }
}
