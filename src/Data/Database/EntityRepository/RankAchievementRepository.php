<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Ramsey\Uuid\UuidInterface;

class RankAchievementRepository extends AbstractEntityRepository
{
    public function findAllForRankId(UuidInterface $rankId): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'achievement')
            ->join('tbl.achievement', 'achievement')
            ->where('IDENTITY(tbl.rank) = :rankId')
            ->orderBy('achievement.displayOrder', 'ASC')
            ->setParameter('rankId', $rankId->getBytes());
        return $qb->getQuery()->getArrayResult();
    }
}
