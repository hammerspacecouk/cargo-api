<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class PlayerRankRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 24 * 2; // 2 days

    public function getList(): array
    {
        $cacheKey = __CLASS__ . '-' . __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->orderBy('tbl.threshold', 'ASC');

        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }

    public function getStarter(
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.threshold = 0')
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
