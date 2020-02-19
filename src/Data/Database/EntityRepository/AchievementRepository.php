<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

class AchievementRepository extends AbstractEntityRepository
{
    public function findAll(): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->orderBy('tbl.displayOrder', 'ASC');
        return $qb->getQuery()->getArrayResult();
    }
}
