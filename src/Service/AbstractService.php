<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\EntityManager;
use App\Data\Database\Mapper\MapperFactory;
use App\Data\TokenProvider;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

abstract class AbstractService
{
    protected const TBL = 'tbl';

    protected const DEFAULT_LIMIT = 50;
    protected const DEFAULT_PAGE = 1;

    protected $entityManager;
    protected $mapperFactory;
    protected $applicationConfig;
    protected $tokenHandler;
    protected $dateTimeFactory;
    protected $cache;
    protected $logger;

    public function __construct(
        EntityManager $entityManager,
        MapperFactory $mapperFactory,
        ApplicationConfig $applicationConfig,
        TokenProvider $tokenHandler,
        DateTimeFactory $dateTimeFactory,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->mapperFactory = $mapperFactory;
        $this->applicationConfig = $applicationConfig;
        $this->tokenHandler = $tokenHandler;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    protected function getQueryBuilder(string $name): QueryBuilder
    {
        $entity = $this->entityManager->getRepository($name);
        return $entity->createQueryBuilder(self::TBL);
    }

    protected function getOffset(int $limit, int $page): int
    {
        return ($limit * ($page - 1));
    }
}
