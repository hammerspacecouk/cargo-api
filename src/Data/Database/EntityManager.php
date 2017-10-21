<?php
declare(strict_types=1);

namespace App\Data\Database;

use App\Data\Database\Entity\AbstractEntity;
use App\Data\Database\EntityRepository\AbstractEntityRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class EntityManager extends DoctrineEntityManager
{
    private $currentTime;
    private $cache;
    private $logger;

    public function __construct(
        Connection $conn,
        Configuration $config,
        EventManager $eventManager,
        DateTimeImmutable $currentTime,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        parent::__construct($conn, $config, $eventManager);
        $this->currentTime = $currentTime;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function persist($entity)
    {
        /** @var AbstractEntity $entity */

        // interject to update the created_at/updated_at fields (for audit purposes)
        $entity->updatedAt = $this->currentTime;
        if (!$entity->createdAt) {
            $entity->createdAt = $this->currentTime;
        }
        parent::persist($entity);
    }

    public function getRepository($entityName)
    {
        /** @var AbstractEntityRepository $repo */
        $repo = parent::getRepository($entityName);

        // set dependencies (which could not be injected via construct)
        $repo->setCurrentTime($this->currentTime);
        $repo->setCache($this->cache);
        $repo->setLogger($this->logger);

        return $repo;
    }

    public function getChannelRepo(): EntityRepository\ChannelRepository
    {
        return $this->getRepository(Entity\Channel::class);
    }

    public function getClusterRepo(): EntityRepository\ClusterRepository
    {
        return $this->getRepository(Entity\Cluster::class);
    }

    public function getCrateRepo(): EntityRepository\CrateRepository
    {
        return $this->getRepository(Entity\Crate::class);
    }

    public function getCrateLocationRepo(): EntityRepository\CrateLocationRepository
    {
        return $this->getRepository(Entity\CrateLocation::class);
    }

    public function getDictionaryRepo(): EntityRepository\DictionaryRepository
    {
        return $this->getRepository(Entity\Dictionary::class);
    }

    public function getPlayerRankRepo(): EntityRepository\PlayerRankRepository
    {
        return $this->getRepository(Entity\PlayerRank::class);
    }

    public function getPlayerStandingRepo(): EntityRepository\PlayerStandingRepository
    {
        return $this->getRepository(Entity\PlayerStanding::class);
    }

    public function getPortRepo(): EntityRepository\PortRepository
    {
        return $this->getRepository(Entity\Port::class);
    }

    public function getShipRepo(): EntityRepository\ShipRepository
    {
        return $this->getRepository(Entity\Ship::class);
    }

    public function getShipClassRepo(): EntityRepository\ShipClassRepository
    {
        return $this->getRepository(Entity\ShipClass::class);
    }

    public function getShipLocationRepo(): EntityRepository\ShipLocationRepository
    {
        return $this->getRepository(Entity\ShipLocation::class);
    }

    public function getTokenRepo(): EntityRepository\TokenRepository
    {
        return $this->getRepository(Entity\Token::class);
    }

    public function getUserRepo(): EntityRepository\UserRepository
    {
        return $this->getRepository(Entity\User::class);
    }
}
