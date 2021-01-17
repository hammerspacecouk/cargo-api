<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\CrateType;

class CrateTypeRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 24 * 2; // 2 days

    public function getRandomInitialCrateContents(): CrateType
    {
        $contents = $this->getAvailableCrateTypesForNewUser();

        /** @noinspection RandomApiMigrationInspection - so it can be seeded */
        return $contents[\mt_rand(0, \count($contents) - 1)];
    }

    public function getRandomCrateContents(bool $excludeGoal = false): CrateType
    {
        $contents = $this->getAvailableCrateTypes($excludeGoal);
        return $this->getRandomWeighted($contents);
    }

    public function getGoalCrateContents(): CrateType
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.isGoal = true');
        return $qb->getQuery()->getSingleResult();
    }

    private function getAvailableCrateTypes(bool $excludeGoal = false): array
    {
        $cacheKey = __CLASS__ . '-' . __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }
        // get all the crate options and their abundance
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->orderBy('tbl.abundance', 'DESC');

        if ($excludeGoal) {
            $qb = $qb->where('tbl.isGoal = false');
        }

        $data = \array_values($qb->getQuery()->getResult());
        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        return $data;
    }

    private function getAvailableCrateTypesForNewUser(): array
    {
        $cacheKey = __CLASS__ . '-' . __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.value = 1');

        $data = \array_values($qb->getQuery()->getResult());
        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        return $data;
    }

    private function getRandomWeighted(array $crateTypes): CrateType
    {
        $total = \array_reduce($crateTypes, static function (int $carry, CrateType $crateType) {
            return $carry + $crateType->abundance;
        }, 0);

        $randomValue = \random_int(0, $total);

        foreach ($crateTypes as $crateType) {
            $randomValue -= $crateType->abundance;
            if ($randomValue <= 0) {
                return $crateType;
            }
        }
        return \end($crateTypes);
    }
}
