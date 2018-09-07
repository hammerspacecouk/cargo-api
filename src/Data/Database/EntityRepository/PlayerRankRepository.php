<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\PlayerRank;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PlayerRankRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 24 * 2; // 2 days

    public function getList(): array
    {
        $cacheKey = __CLASS__ . '-' . __METHOD__;
        if ($data = $this->cache->get($cacheKey)) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->orderBy('tbl.threshold', 'ASC');

        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }
}
