<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class PortRepository extends AbstractEntityRepository
{
    public function getARandomSafePort(
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $safeCount = $this->countSafe();
        $randomOffset = rand(0, $safeCount - 1);

        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.isSafeHaven = true')
            ->andWhere('tbl.isOpen = true')
            ->setFirstResult($randomOffset)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function countSafe(): int
    {
        $cacheKey = __CLASS__ . '-' . __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $result = (int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->where('tbl.isSafeHaven = true')
            ->andWhere('tbl.isOpen = true')
            ->getQuery()
            ->getSingleScalarResult();

        $this->cache->set($cacheKey, $result, 60 * 60);
        return $result;
    }
}
