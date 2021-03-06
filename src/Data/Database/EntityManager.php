<?php
declare(strict_types=1);

namespace App\Data\Database;

use App\Data\Database\Entity\AbstractEntity;
use App\Data\Database\EntityRepository\AbstractEntityRepository;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class EntityManager extends EntityManagerDecorator
{
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private ApplicationConfig $applicationConfig;

    /**
     * @var array<string, AbstractEntityRepository>
     */
    private array $classCache = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        ApplicationConfig $applicationConfig,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        parent::__construct($entityManager);
        $this->cache = $cache;
        $this->logger = $logger;
        $this->applicationConfig = $applicationConfig;
    }

    /**
     * @param AbstractEntity $entity
     */
    public function persist($entity): void
    {
        // interject to update the created_at/updated_at fields (for audit purposes)
        $now = DateTimeFactory::now();
        $entity->updatedAt = $now;
        if (!$entity->createdAt) {
            $entity->createdAt = $now;
        }
        parent::persist($entity);
    }

    /**
     * @param class-string<mixed> $entityName
     * @return AbstractEntityRepository
     */
    public function getRepository($entityName)
    {
        if (!isset($this->classCache[$entityName])) {
            /** @var AbstractEntityRepository $repo */
            $repo = parent::getRepository($entityName);

            // set dependencies (which could not be injected via construct)
            $repo->setEntityManager($this);
            $repo->setApplicationConfig($this->applicationConfig);
            $repo->setCache($this->cache);
            $repo->setLogger($this->logger);

            $this->classCache[$entityName] = $repo;
        }

        return $this->classCache[$entityName];
    }

    public function getAll(): array
    {
        $entityFiles = \scandir(__DIR__ . '/Entity/', SCANDIR_SORT_NONE);
        if (!$entityFiles) {
            return [];
        }

        $results = \array_map(function ($className) {
            $fullEntityName = __NAMESPACE__ . '\\Entity\\' . \str_replace('.php', '', $className);
            if (\class_exists($fullEntityName) && \is_subclass_of($fullEntityName, AbstractEntity::class)) {
                return $this->getRepository($fullEntityName);
            }

            return null;
        }, $entityFiles);

        return array_filter($results);
    }

    public function getAchievementRepo(): EntityRepository\AchievementRepository
    {
        return $this->getRepository(Entity\Achievement::class);
    }

    public function getActiveEffectRepo(): EntityRepository\ActiveEffectRepository
    {
        return $this->getRepository(Entity\ActiveEffect::class);
    }

    public function getAuthenticationTokenRepo(): EntityRepository\AuthenticationTokenRepository
    {
        return $this->getRepository(Entity\AuthenticationToken::class);
    }

    public function getChannelRepo(): EntityRepository\ChannelRepository
    {
        return $this->getRepository(Entity\Channel::class);
    }

    public function getConfigRepo(): EntityRepository\ConfigRepository
    {
        return $this->getRepository(Entity\Config::class);
    }

    public function getCrateRepo(): EntityRepository\CrateRepository
    {
        return $this->getRepository(Entity\Crate::class);
    }

    public function getCrateLocationRepo(): EntityRepository\CrateLocationRepository
    {
        return $this->getRepository(Entity\CrateLocation::class);
    }

    public function getCrateTypeRepo(): EntityRepository\CrateTypeRepository
    {
        return $this->getRepository(Entity\CrateType::class);
    }

    public function getDictionaryRepo(): EntityRepository\DictionaryRepository
    {
        return $this->getRepository(Entity\Dictionary::class);
    }

    public function getEffectRepo(): EntityRepository\EffectRepository
    {
        return $this->getRepository(Entity\Effect::class);
    }

    public function getEventRepo(): EntityRepository\EventRepository
    {
        return $this->getRepository(Entity\Event::class);
    }

    public function getHintRepo(): EntityRepository\HintRepository
    {
        return $this->getRepository(Entity\Hint::class);
    }

    public function getPlayerRankRepo(): EntityRepository\PlayerRankRepository
    {
        return $this->getRepository(Entity\PlayerRank::class);
    }

    public function getPortRepo(): EntityRepository\PortRepository
    {
        return $this->getRepository(Entity\Port::class);
    }

    public function getPortVisitRepo(): EntityRepository\PortVisitRepository
    {
        return $this->getRepository(Entity\PortVisit::class);
    }

    public function getPurchaseRepo(): EntityRepository\PurchaseRepository
    {
        return $this->getRepository(Entity\Purchase::class);
    }

    public function getRankAchievementRepo(): EntityRepository\RankAchievementRepository
    {
        return $this->getRepository(Entity\RankAchievement::class);
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

    public function getUsedActionTokenRepo(): EntityRepository\UsedActionTokenRepository
    {
        return $this->getRepository(Entity\UsedActionToken::class);
    }

    public function getUserAchievementRepo(): EntityRepository\UserAchievementRepository
    {
        return $this->getRepository(Entity\UserAchievement::class);
    }

    public function getUserEffectRepo(): EntityRepository\UserEffectRepository
    {
        return $this->getRepository(Entity\UserEffect::class);
    }

    public function getUserRepo(): EntityRepository\UserRepository
    {
        return $this->getRepository(Entity\User::class);
    }
}
