<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\EntityManager;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractEntityRepository extends EntityRepository
{
    /** @var DateTimeFactory */
    protected $dateTimeFactory;

    /** @var ApplicationConfig */
    protected $applicationConfig;

    /** @var CacheInterface */
    protected $cache;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EntityManager */
    protected $_em; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- must match parent

    /*
     * We are away from dependency injection via constructors territory, so we have to rely on the (risky) strategy
     * of having setters for these. Everything is safe and predictable as long as repositories are only EVER called
     * via our custom EntityManager and ALL entities have a repository which extends this class
     */
    public function setApplicationConfig(ApplicationConfig $config): void
    {
        $this->applicationConfig = $config;
    }

    public function setDateTimeFactory(DateTimeFactory $dateTimeFactory): void
    {
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setEntityManager(EntityManager $em): void
    {
        $this->_em = $em;
    }

    /**
     * @param UuidInterface $uuid
     * @param int $resultType
     * @return mixed
     */
    public function getByID(
        UuidInterface $uuid,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function deleteById(UuidInterface $uuid, string $className): void
    {
        $sql = 'DELETE FROM ' . $className . ' t WHERE t.id = :id';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('id', $uuid->getBytes());
        $query->execute();
    }

    /** @noinspection SenselessMethodDuplicationInspection */
    protected function getEntityManager(): EntityManager
    {
        return $this->_em;
    }
}
