<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class EffectRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 4;

    public function getAll(): array
    {
        $cacheKey = __CLASS__ . __METHOD__;
        if ($data = $this->cache->get($cacheKey)) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'minimumRank')
            ->join('tbl.minimumRank', 'minimumRank')
            ->orderBy('tbl.orderNumber', 'ASC');

        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }
}
