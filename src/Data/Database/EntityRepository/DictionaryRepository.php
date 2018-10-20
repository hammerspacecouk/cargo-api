<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Dictionary;
use function App\Functions\Arrays\seedableRandomItem;
use Doctrine\ORM\Query;

class DictionaryRepository extends AbstractEntityRepository
{
    private const CACHE_LIFETIME = (60 * 60 * 24 * 2); // 2 days

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

    public function getRandomCrateContents(): array
    {
        return seedableRandomItem($this->getCrateContentsList());
    }

    private function getCrateContentsList(): array
    {
        $cacheKey = 'DictionaryRepository-crateContentsList';
        $contents = $this->cache->get($cacheKey);
        if ($contents) {
            return $contents;
        }
        // get all the words and their abundance
        $all = $this->getAllByContext(Dictionary::CONTEXT_CRATE_CONTENTS);

        // get the highest abundance value (already ordered)
        $maxAbundance = $all[0]['abundance'];

        // make an array containing each number of items according to their abundance
        $data = [];
        foreach ($all as $item) {
            $value = $maxAbundance / $item['abundance'];
            for ($i = 0; $i < $item['abundance']; $i++) {
                $data[] = [$item['word'], $value];
            }
        }
        $this->cache->set($cacheKey, $data, self::CACHE_LIFETIME);
        return $data;
    }

    private function getAllByContext(string $context): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.context = :context')
            ->orderBy('tbl.abundance', 'DESC')
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
