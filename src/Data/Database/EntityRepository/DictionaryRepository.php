<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Dictionary;
use Doctrine\ORM\Query;

class DictionaryRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60 * 60 * 24 * 2; // 2 days

    public function wordExistsInContext(string $word, string $context): bool
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('count(tbl.id)')
            ->where('tbl.word = :word')
            ->andWhere('tbl.context = :context')
            ->setParameter('word', $word)
            ->setParameter('context', $context);
        return !!$qb->getQuery()->getSingleScalarResult();
    }

    public function getRandomShipName(): string
    {
        return 'The ' . $this->getRandomShipNameFirst() . ' ' . $this->getRandomShipNameSecond();
    }

    public function getRandomShipNameFirst(): string
    {
        return $this->getRandomWord(Dictionary::CONTEXT_SHIP_NAME_1);
    }

    public function getRandomWord(string $context): string
    {
        $words = $this->getAllByContext($context);
        return $words[array_rand($words)];
    }

    private function getAllByContext(string $context): array
    {
        $cacheKey = 'DictionaryRepository-getAll-' . $context;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl.word')
            ->where('tbl.context = :context')
            ->setParameter('context', $context);

        $data = array_map(function ($result) {
            return $result['word'];
        }, $qb->getQuery()->getResult(Query::HYDRATE_ARRAY));

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }

    public function getRandomShipNameSecond(): string
    {
        return $this->getRandomWord(Dictionary::CONTEXT_SHIP_NAME_2);
    }
}
