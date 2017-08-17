<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Channel as DbChannel;
use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\Dictionary as DbDictionary;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipClass as DbShipClass;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\Database\Entity\Token as DbToken;
use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\EntityRepository\ChannelRepository;
use App\Data\Database\EntityRepository\CrateLocationRepository;
use App\Data\Database\EntityRepository\CrateRepository;
use App\Data\Database\EntityRepository\DictionaryRepository;
use App\Data\Database\EntityRepository\PortRepository;
use App\Data\Database\EntityRepository\ShipClassRepository;
use App\Data\Database\EntityRepository\ShipLocationRepository;
use App\Data\Database\EntityRepository\ShipRepository;
use App\Data\Database\EntityRepository\TokenRepository;
use App\Data\Database\EntityRepository\UserRepository;
use App\Data\Database\Mapper\MapperFactory;
use App\Data\TokenHandler;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractService
{
    protected const TBL = 'tbl';

    protected const DEFAULT_LIMIT = 50;
    protected const DEFAULT_PAGE = 1;

    protected $entityManager;
    protected $mapperFactory;
    protected $tokenHandler;
    protected $currentTime;

    public function __construct(
        EntityManager $entityManager,
        MapperFactory $mapperFactory,
        TokenHandler $tokenHandler,
        DateTimeImmutable $currentTime
    ) {
        $this->entityManager = $entityManager;
        $this->mapperFactory = $mapperFactory;
        $this->tokenHandler = $tokenHandler;
        $this->currentTime = $currentTime;
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

    protected function getChannelRepo(): ChannelRepository
    {
        return $this->entityManager->getRepository(DbChannel::class);
    }

    protected function getCrateRepo(): CrateRepository
    {
        return $this->entityManager->getRepository(DbCrate::class);
    }

    protected function getCrateLocationRepo(): CrateLocationRepository
    {
        return $this->entityManager->getRepository(DbCrateLocation::class);
    }

    protected function getDictionaryRepo(): DictionaryRepository
    {
        return $this->entityManager->getRepository(DbDictionary::class);
    }

    protected function getPortRepo(): PortRepository
    {
        return $this->entityManager->getRepository(DbPort::class);
    }

    protected function getShipRepo(): ShipRepository
    {
        return $this->entityManager->getRepository(DbShip::class);
    }

    protected function getShipClassRepo(): ShipClassRepository
    {
        return $this->entityManager->getRepository(DbShipClass::class);
    }

    protected function getShipLocationRepo(): ShipLocationRepository
    {
        return $this->entityManager->getRepository(DbShipLocation::class);
    }

    protected function getTokenRepo(): TokenRepository
    {
        return $this->entityManager->getRepository(DbToken::class);
    }

    protected function getUserRepo(): UserRepository
    {
        return $this->entityManager->getRepository(DbUser::class);
    }
}
