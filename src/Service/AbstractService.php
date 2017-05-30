<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Mapper\MapperFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractService
{
    protected const TBL = 'tbl';

    protected const DEFAULT_LIMIT = 50;
    protected const DEFAULT_PAGE = 1;

    protected $entityManager;
    protected $mapperFactory;

    public function __construct(
        EntityManager $entityManager,
        MapperFactory $mapperFactory
    ) {
        $this->entityManager = $entityManager;
        $this->mapperFactory = $mapperFactory;
    }

    protected function getQueryBuilder(string $name): QueryBuilder
    {
        $entity = $this->entityManager->getRepository($name);
        return $entity->createQueryBuilder(self::TBL);
    }

    protected function getOffset(int $limit, int $page): int {
        return ($limit * ($page - 1));
    }
}
