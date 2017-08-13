<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Dictionary;
use Doctrine\ORM\Query;

class DictionaryRepository extends AbstractEntityRepository
{
    private static $contextCache = [];

    private function getAllByContext(string $context): array
    {
        if (isset(self::$contextCache[$context])) {
            return self::$contextCache[$context];
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl.word')
            ->where('tbl.context = :context')
            ->setParameter('context', $context)
        ;

        $data = array_map(function($result) {
            return $result['word'];
        }, $qb->getQuery()->getResult(Query::HYDRATE_ARRAY));

        self::$contextCache[$context] = $data;

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
