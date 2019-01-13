<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Types\EnumEffectsType;
use Doctrine\ORM\Query;
use InvalidArgumentException;

class EffectRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 4;

    public function getAllByType(string $type): array
    {
        if (!\in_array($type, EnumEffectsType::ALL_TYPES, true)) {
            throw new InvalidArgumentException($type . ' is not an effect type');
        }

        return \array_values(\array_filter($this->getAll(), function($result) use ($type) {
            return $result['type'] === $type;
        }));
    }

    private function getAll(): array
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
