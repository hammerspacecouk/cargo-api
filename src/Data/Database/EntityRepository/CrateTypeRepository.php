<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\CrateType;
use Doctrine\ORM\Query;

class CrateTypeRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 24 * 2; // 2 days

    public function getRandomCrateContents(): CrateType
    {
        $contents = $this->getAvailableCrateTypesForNewUser();
        return $this->getRandomWeighted($contents);
    }

    private function getAvailableCrateTypesForNewUser(): array
    {
        $cacheKey = __CLASS__ . __METHOD__;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }
        // get all the crate options and their abundance
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.isGoal = 0')
            ->orderBy('tbl.abundance', 'DESC');

        $data = $qb->getQuery()->getResult();

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        return $data;
    }

    private function getRandomWeighted(array $crateTypes): CrateType
    {
        $total = \array_reduce($crateTypes, function(int $carry, CrateType $crateType) {
            return $carry + $crateType->abundance;
        }, 0);

        /** @noinspection RandomApiMigrationInspection - so it can be seeded */
        $randomValue = \mt_rand(0, $total);

        foreach ($crateTypes as $crateType) {
            $randomValue -= $crateType->abundance;
            if ($randomValue <= 0) {
                return $crateType;
            }
        }
        return \end($crateType);
    }


}
