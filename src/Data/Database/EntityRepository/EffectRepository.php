<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class EffectRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 4;

    public function getAll(int $type = Query::HYDRATE_ARRAY): array
    {
        $cacheKey = __CLASS__ . __METHOD__ . $type;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'minimumRank')
            ->join('tbl.minimumRank', 'minimumRank')
            ->orderBy('tbl.orderNumber', 'ASC');

        $data = $qb->getQuery()->getResult($type);

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }

    public function getAllByDisplayType(string $type): array
    {
        return \array_values(\array_filter($this->getAll(), static function ($result) use ($type) {
            return $result['displayGroup'] === $type;
        }));
    }


    public function getAllAboveRankThreshold(int $threshold, int $type = Query::HYDRATE_ARRAY): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'minimumRank')
            ->join('tbl.minimumRank', 'minimumRank')
            ->where('minimumRank.threshold <= :threshold')
            ->orderBy('tbl.orderNumber', 'ASC')
            ->setParameter('threshold', $threshold);

        return $qb->getQuery()->getResult($type);
    }

    public function getByType(
        string $type,
        int $resultType = Query::HYDRATE_ARRAY
    ): array {
        $cacheKey = __CLASS__ . __METHOD__ . $type . '-' . $resultType;
        $canCache = $resultType === Query::HYDRATE_ARRAY;
        $data = $this->cache->get($cacheKey);
        if ($canCache && $data) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'minimumRank')
            ->join('tbl.minimumRank', 'minimumRank')
            ->where('tbl.type = :type')
            ->orderBy('tbl.orderNumber', 'ASC')
            ->setParameter('type', $type);

        $data = $qb->getQuery()->getResult($resultType);

        if ($canCache) {
            $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        }

        return $data;
    }

    public function getTypeAboveRankThreshold(
        string $type,
        int $threshold,
        int $resultType = Query::HYDRATE_ARRAY
    ): array {
        $cacheKey = __CLASS__ . __METHOD__ . $type . '-' . $threshold . '-' . $resultType;
        $canCache = $resultType === Query::HYDRATE_ARRAY;
        $data = $this->cache->get($cacheKey);
        if ($canCache && $data) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'minimumRank')
            ->join('tbl.minimumRank', 'minimumRank')
            ->where('minimumRank.threshold <= :threshold')
            ->andWhere('tbl.type = :type')
            ->orderBy('tbl.orderNumber', 'ASC')
            ->setParameter('type', $type)
            ->setParameter('threshold', $threshold);

        $data = $qb->getQuery()->getResult($resultType);

        if ($canCache) {
            $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        }

        return $data;
    }
}
