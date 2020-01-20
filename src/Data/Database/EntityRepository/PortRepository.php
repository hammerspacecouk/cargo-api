<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class PortRepository extends AbstractEntityRepository
{
    /**
     * @param int $resultType
     * @return mixed
     */
    public function getARandomStarterPort(
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $safeCount = $this->countHomes();
        /** @noinspection RandomApiMigrationInspection - uses mt_rand so it can be seeded */
        $randomOffset = mt_rand(0, $safeCount - 1);

        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.isAHome = true')
            ->andWhere('tbl.isOpen = true')
            ->andWhere('tbl.isDestination = false')
            ->setFirstResult($randomOffset)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function getARandomDangerousPort(
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $safeCount = $this->countDangerous();
        $randomOffset = random_int(0, $safeCount - 1);

        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.isAHome = false')
            ->andWhere('tbl.isOpen = true')
            ->andWhere('tbl.isDestination = false')
            ->andWhere('tbl.isSafeHaven = false')
            ->setFirstResult($randomOffset)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    private function countHomes(): int
    {
        $cacheKey = __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $result = (int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->where('tbl.isAHome = true')
            ->andWhere('tbl.isOpen = true')
            ->andWhere('tbl.isDestination = false')
            ->getQuery()
            ->getSingleScalarResult();

        $this->cache->set($cacheKey, $result, 60 * 60);
        return $result;
    }

    private function countDangerous(): int
    {
        $cacheKey = __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $result = (int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->where('tbl.isAHome = false')
            ->andWhere('tbl.isOpen = true')
            ->andWhere('tbl.isDestination = false')
            ->andWhere('tbl.isSafeHaven = false')
            ->getQuery()
            ->getSingleScalarResult();

        $this->cache->set($cacheKey, $result, 60 * 60);
        return $result;
    }
}
