<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\User;
use App\Data\Database\EntityManager;
use App\Data\Database\Mapper\MapperFactory;
use App\Data\TokenProvider;
use App\Domain\Exception\IllegalMoveException;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Uuid\UuidFactoryInterface;

abstract class AbstractService
{
    protected const TBL = 'tbl';

    protected const DEFAULT_LIMIT = 50;
    protected const DEFAULT_PAGE = 1;

    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var MapperFactory
     */
    protected $mapperFactory;
    /**
     * @var ApplicationConfig
     */
    protected $applicationConfig;
    /**
     * @var TokenProvider
     */
    protected $tokenHandler;
    /**
     * @var DateTimeFactory
     */
    protected $dateTimeFactory;
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var UuidFactoryInterface
     */
    protected $uuidFactory;

    public function __construct(
        EntityManager $entityManager,
        MapperFactory $mapperFactory,
        ApplicationConfig $applicationConfig,
        TokenProvider $tokenHandler,
        DateTimeFactory $dateTimeFactory,
        UuidFactoryInterface $uuidFactory,
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
        $this->uuidFactory = $uuidFactory;
    }

    /**
     * @param class-string<mixed> $name
     * @return QueryBuilder
     */
    protected function getQueryBuilder($name): QueryBuilder
    {
        $entity = $this->entityManager->getRepository($name);
        return $entity->createQueryBuilder(self::TBL);
    }

    protected function getOffset(int $limit, int $page): int
    {
        return ($limit * ($page - 1));
    }

    protected function consumeCredits(User $userEntity, int $credits): void
    {
        $userRepo = $this->entityManager->getUserRepo();
        // check the user has enough credits
        if ($userRepo->currentScore($userEntity) < $credits) {
            throw new IllegalMoveException($credits . ' required');
        }
        // save their new credits value
        $userRepo->updateScoreValue($userEntity, -$credits);
    }
}
