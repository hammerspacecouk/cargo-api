<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Dictionary;
use function App\Functions\Arrays\seedableRandomItem;
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
        return (bool)$qb->getQuery()->getSingleScalarResult();
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
        return seedableRandomItem($this->getAllWordsByContext($context));
    }

    public function getRandomShipNameSecond(): string
    {
        return $this->getRandomWord(Dictionary::CONTEXT_SHIP_NAME_2);
    }

    private function getAllByContext(string $context): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.context = :context')
            ->setParameter('context', $context);

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    private function getAllWordsByContext(string $context): array
    {
        $cacheKey = 'DictionaryRepository-getAllWords-' . $context;
        $data = $this->cache->get($cacheKey);
        if ($data) {
            return $data;
        }

        $data = array_map(function ($result) {
            return $result['word'];
        }, $this->getAllByContext($context));

        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }
}
