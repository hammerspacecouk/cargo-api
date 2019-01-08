<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class HintRepository extends AbstractEntityRepository
{
    public const ANON_HINT = 'Your account is currently anonymous. ' .
        'You will lose your game if you clear your cookies. ' .
        'Visit your profile to assign an e-mail address to your account.';

    private const CACHE_LIFETIME = 60 * 60 * 24 * 2; // 2 days

    public function getRandomForRankThreshold(int $threshold): string
    {
        $hints = $this->getAllForThreshold($threshold);
        return $hints[\array_rand($hints)]['text'];
    }

    private function getAllForThreshold(int $threshold): array
    {
        return \array_filter($this->getAll(), function(array $hint) use ($threshold) {
            if (isset($hint['rank'])) {
                return ($hint['rank']['threshold'] <= $threshold);
            }
            return true;
        });
    }

    private function getAll(): array
    {
        $cacheKey = __CLASS__ . __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'rank')
            ->leftJoin('tbl.minimumRank', 'rank');
        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        return $data;
    }
}
