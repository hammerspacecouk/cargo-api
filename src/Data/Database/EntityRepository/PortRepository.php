<?php declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class PortRepository extends AbstractEntityRepository
{
    public function getARandomSafePort(
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $safeCount = $this->countSafe();
        $randomOffset = mt_rand(0, $safeCount - 1);

        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.isSafeHaven = true')
            ->setFirstResult($randomOffset)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function countSafe(): int
    {
        return (int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->where('tbl.isSafeHaven = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
