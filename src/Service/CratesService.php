<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\EntityRepository\CrateRepository;
use App\Domain\Entity\Crate;
use App\Domain\Entity\Port;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CratesService extends AbstractService
{
    const CONTENTS = [
        '🎂',
        '🚗',
        '🛴',
        '🎈',
        '🚽',
        '🏀',
        '⛸',
        '🏓',
        '🎷',
        '🔨',
        '💾',
        '🗿',
        '💰',
        '✉',
    ];

    public function makeNew(): void
    {
        $crate = new DbCrate(self::CONTENTS[array_rand(self::CONTENTS)]);

        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

    public function countAllAvailable(): int
    {
        $qb = $this->getQueryBuilder(DbCrate::class)
            ->select('count(1)')
            ->innerJoin(DbCrateLocation::class, 'location', Join::WITH, 'location.crate = tbl')
        ;
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAvailable(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbCrate::class)
            ->innerJoin(DbCrateLocation::class, 'location', Join::WITH, 'location.crate = tbl')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createCrateMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result);
        }, $results);
    }

    public function getByID(
        UuidInterface $uuid
    ): ?Crate {
        $result = $this->getCrateRepo()->getActiveByID($uuid);

        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createCrateMapper();
        return $mapper->getCrate($result[0]);
    }

    public function getByIDWithLocation(
        UuidInterface $uuid
    ): ?Crate {
        $result = $this->getCrateRepo()->getActiveByID($uuid);

        if (!$result) {
            return null;
        }

        $location = $this->getCrateLocationRepo()
            ->getCurrentForCrateID($uuid);

        $mapper = $this->mapperFactory->createCrateMapper();
        return $mapper->getCrate($result, $location);
    }

    public function countForPort(Port $port): int
    {
        $qb = $this->getQueryBuilder(DbCrateLocation::class)
            ->select('count(1)')
            ->where('IDENTITY(tbl.port) = :portID')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('portID', $port->getId()->getBytes())
        ;
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findActiveForPort(
        Port $port,
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbCrateLocation::class)
            ->select('tbl', 'c')
            ->join('tbl.crate', 'c')
            ->where('IDENTITY(tbl.port) = :portID')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('portID', $port->getId()->getBytes())
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createCrateMapper();

        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result['crate']);
        }, $results);
    }

    public function moveCrateToPort(
        Uuid $crateId,
        Uuid $portId
    ): void {
        /** @var CrateRepository $crateRepo */
        $crateRepo = $this->getCrateRepo();

        // fetch the crate and the port
        $crate = $crateRepo->getById($crateId, Query::HYDRATE_OBJECT);
        if (!$crate) {
            throw new \InvalidArgumentException('No such active crate');
        }

        $port = $this->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        if (!$port) {
            throw new \InvalidArgumentException('No such port');
        }

        // make a new crate location
        $newLocation = new DbCrateLocation(
            $crate,
            $port,
            null
        );
        $newLocation->isCurrent = true;

        $this->entityManager->persist($newLocation);
        $this->entityManager->flush();
    }
}
