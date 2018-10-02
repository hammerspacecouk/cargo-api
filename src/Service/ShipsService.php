<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipsService extends AbstractService
{
    public function makeNew(User $owner): void
    {
        // all ships begin as starter ships, and must be upgraded
        $starterShip = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);

        $user = $this->entityManager->getUserRepo()
            ->getByID($owner->getId(), Query::HYDRATE_OBJECT);

        $ship = new DbShip(
            $this->entityManager->getDictionaryRepo()->getRandomShipName(),
            $starterShip,
            $user
        );
        $this->entityManager->persist($ship);

        // new ships need to be put into a safe port
        $safePort = $this->entityManager->getPortRepo()
            ->getARandomSafePort(Query::HYDRATE_OBJECT);

        $location = new DbShipLocation(
            $ship,
            $safePort,
            null,
            $this->dateTimeFactory->now()
        );

        $this->entityManager->persist($location);

        $this->entityManager->flush();
    }

    public function countAll(): int
    {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('count(1)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function findAll(
        int $limit,
        int $page = 1
    ): array {
        $this->logger->info(__CLASS__ . ':' . __METHOD__);

        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page));

        $mapper = $this->mapperFactory->createShipMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }

    public function getByID(
        UuidInterface $uuid
    ): ?Ship {
    
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes());

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($result);
    }

    public function shipOwnedBy(
        UuidInterface $shipId,
        UuidInterface $ownerId
    ) {
        return !!$this->getByIDForOwnerId($shipId, $ownerId);
    }

    public function getByIDForOwnerId(
        UuidInterface $shipId,
        UuidInterface $ownerId
    ): ?Ship {
    
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->andWhere('IDENTITY(tbl.owner) = :ownerId')
            ->setParameter('id', $shipId->getBytes())
            ->setParameter('ownerId', $ownerId->getBytes());

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($result);
    }

    public function getByIDWithLocation(
        UuidInterface $uuid
    ): ?Ship {
    
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes());

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$result) {
            return null;
        }

        $result['location'] = $this->entityManager->getShipLocationRepo()
            ->getCurrentForShipId($uuid);

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($result);
    }

    public function countForOwnerIDWithLocation(
        UuidInterface $userId
    ): int {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('count(1)')
            ->where('IDENTITY(tbl.owner) = :id')
            ->setParameter('id', $userId->getBytes());
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function getForOwnerIDWithLocation(
        UuidInterface $userId,
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('IDENTITY(tbl.owner) = :id')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
            ->setParameter('id', $userId->getBytes());

        $results = $qb->getQuery()->getArrayResult();
        $results = $this->attachLocationToShips($results);

        return $this->mapMany($results);
    }

    public function findAllInPort(Port $port): array
    {
        $results = $this->entityManager->getShipLocationRepo()->getShipsForPortId($port->getId());
        return $this->mapMany($results);
    }

    private function attachLocationToShips(array $ships): array
    {
        if (empty($ships)) {
            return $ships;
        }

        // get all the IDs
        $ids = array_map(function ($ship) {
            return $ship['id']->getBytes();
        }, $ships);

        // do a batch query to find all the location and key them by ship
        $locations = [];
        foreach ($this->entityManager->getShipLocationRepo()->getCurrentForShipIds($ids) as $location) {
            $locations[$location['ship']['uuid']] = $location;
        }

        $shipsWithLocations = [];
        foreach ($ships as $ship) {
            $ship['location'] = $locations[$ship['uuid']] ?? null;
            $shipsWithLocations[] = $ship;
        }

        return $shipsWithLocations;
    }

    /** @return Ship[] */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createShipMapper();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }
}
