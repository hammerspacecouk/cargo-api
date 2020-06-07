<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Ramsey\Uuid\UuidInterface;

class PurchaseRepository extends AbstractEntityRepository
{
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
}
