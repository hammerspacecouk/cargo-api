<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\EntityRepository\CrateLocationRepository;
use App\Data\Database\EntityRepository\CrateRepository;
use App\Data\Database\EntityRepository\PortRepository;
use App\Domain\Entity\Crate;
use App\Domain\Entity\Port;
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

    public function makeNew():void
    {
        $crate = new DbCrate(self::CONTENTS[array_rand(self::CONTENTS)]);

        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

    public function countAllAvailable()
    {
        $qb = $this->getQueryBuilder(DbCrate::class)
            ->select('count(1)')
            ->where('tbl.status != :inactiveStatus')
            ->setParameter('inactiveStatus', DbCrate::STATUS_INACTIVE)
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAvailable(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbCrate::class)
            ->where('tbl.status != :inactiveStatus')
            ->setParameter('inactiveStatus', DbCrate::STATUS_INACTIVE)
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createCrateMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result);
        }, $results);
    }

    public function findByID(
        UuidInterface $uuid
    ): ?Crate {
        $results = $this->entityManager->getRepository(DbCrate::class)
            ->queryActiveByID($uuid)
            ->getArrayResult();

        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createCrateMapper();
        return $mapper->getCrate($results[0]);
    }

    public function countForPort(Port $port)
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

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result['crate']);
        }, $results);
    }

    public function moveCrateToPort(
        Uuid $crateId,
        Uuid $portId
    ): void {
        /** @var CrateRepository $crateRepo */
        $crateRepo = $this->entityManager->getRepository(DbCrate::class);
        /** @var PortRepository $portRepo */
        $portRepo = $this->entityManager->getRepository(DbPort::class);
        /** @var CrateLocationRepository $locoRepo */
        $locoRepo = $this->entityManager->getRepository(DbCrateLocation::class);

        // fetch the crate and the port
        $crate = $crateRepo->queryByID($crateId)->getOneOrNullResult();
        if (!$crate || $crate->status === DbCrate::STATUS_INACTIVE) {
            throw new \InvalidArgumentException('No such active crate');
        }

        $port = $portRepo->queryByID($portId)->getOneOrNullResult();
        if (!$port) {
            throw new \InvalidArgumentException('No such port');
        }

        // fetch the current crate location
        /** @var DbCrateLocation|null $currentLocation */
        $currentLocation = $locoRepo->queryCurrentLocationForCrateId($crate->id)
            ->getOneOrNullResult();

        // disable the old location (if there is one)
        if ($currentLocation) {
            $currentLocation->isCurrent = false;
            $this->entityManager->persist($currentLocation);
        }

        // make a new crate location
        $newLocation = new DbCrateLocation();
        $newLocation->isCurrent = true;
        $newLocation->crate = $crate;
        $newLocation->port = $port;

        $this->entityManager->persist($newLocation);
        $this->entityManager->flush();
    }
}
