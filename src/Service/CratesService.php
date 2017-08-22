<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\ID;
use App\Domain\Entity\Crate;
use App\Domain\Entity\Port;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
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
        $crate = new DbCrate(
            ID::makeNewID(DbCrate::class),
            self::CONTENTS[array_rand(self::CONTENTS)]
        );

        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

    public function countAllAvailable(): int
    {
        $qb = $this->getQueryBuilder(DbCrate::class)
            ->select('count(1)')
            ->innerJoin(DbCrateLocation::class, 'location', Join::WITH, 'location.crate = tbl')
            ->where('location.isCurrent = true')
        ;
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findActive(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbCrate::class)
            ->innerJoin(DbCrateLocation::class, 'location', Join::WITH, 'location.crate = tbl')
            ->where('location.isCurrent = true')
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
        $result = $this->entityManager->getCrateRepo()->getByID($uuid);

        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createCrateMapper();
        return $mapper->getCrate($result);
    }

    public function getByIDWithLocation(
        UuidInterface $uuid
    ): ?Crate {
        $result = $this->entityManager->getCrateRepo()->getByID($uuid);

        if (!$result) {
            return null;
        }

        $result['location'] = $this->entityManager->getCrateLocationRepo()
            ->getCurrentForCrateID($uuid);

        $mapper = $this->mapperFactory->createCrateMapper();
        return $mapper->getCrate($result);
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

    public function moveCrateToLocation(
        UuidInterface $crateID,
        UuidInterface $locationId
    ): void {
        $crateRepo = $this->entityManager->getCrateRepo();

        // fetch the crate and the port
        $crate = $crateRepo->getByID($crateID, Query::HYDRATE_OBJECT);
        if (!$crate) {
            throw new \InvalidArgumentException('No such active crate');
        }

        $port = null;
        $ship = null;
        $locationType = ID::getIDType($locationId);

        switch ($locationType) {
            case DbPort::class:
                $port = $this->entityManager->getPortRepo()->getByID($locationId, Query::HYDRATE_OBJECT);
                break;
            case DbShip::class:
                $ship = $this->entityManager->getShipRepo()->getByID($locationId, Query::HYDRATE_OBJECT);
                break;
        }

        if (!$port && !$ship) {
            throw new \InvalidArgumentException('Invalid destination ID');
        }

        // start the transaction
        $this->entityManager->transactional(function () use ($crate, $port, $ship) {
            // remove any old crate locations
            $this->entityManager->getCrateLocationRepo()->disableAllActiveForCrateID($crate->id);

            // make a new crate location
            $newLocation = new DbCrateLocation(
                ID::makeNewID(DbCrateLocation::class),
                $crate,
                $port,
                $ship
            );

            $this->entityManager->persist($newLocation);
            $this->entityManager->flush();
        });
    }
}
