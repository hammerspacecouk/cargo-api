<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Dictionary;
use Doctrine\ORM\Query;

class DictionaryRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = 60*60*24*2; // 2 days

    private function getAllByContext(string $context): array
    {
        $cacheKey = 'DictionaryRepository-getAll-' . $context;
        $this->logger->debug('Checking cache for ' . $cacheKey);
        $data = $this->cache->get($cacheKey);
        if ($data) {
            $this->logger->debug('Cache HIT for ' . $cacheKey);
            return $data;
        }
        $this->logger->debug('Cache MISS for ' . $cacheKey);

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl.word')
            ->where('tbl.context = :context')
            ->setParameter('context', $context)
        ;

        $data = array_map(function ($result) {
            return $result['word'];
        }, $qb->getQuery()->getResult(Query::HYDRATE_ARRAY));

        $this->logger->debug('Caching for ' . self::CACHE_LIFETIME . ' seconds');
        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);

        return $data;
    }

    public function getRandomWord(string $context): string
    {
        $words = $this->getAllByContext($context);
        return $words[array_rand($words)];
    }

    public function getRandomShipName(): string
    {
        return 'The ' . $this->getRandomShipNameFirst() . ' ' . $this->getRandomShipNameSecond();
    }

    public function getRandomShipNameFirst(): string
    {
        return $this->getRandomWord(Dictionary::CONTEXT_SHIP_NAME_1);
    }

    public function getRandomShipNameSecond(): string
    {
        return $this->getRandomWord(Dictionary::CONTEXT_SHIP_NAME_2);
    }
}
