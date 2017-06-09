<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\ID;
use App\Domain\Entity\Ship as ShipEntity;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipsService extends AbstractService
{
    const STARTER_SHIP_UUID = 'c274d46f-5b3b-433c-81a8-ac9f97247699';

    public function makeNew(): void
    {
        $starterShip = $this->getShipClassRepo()
            ->getById(Uuid::fromString(self::STARTER_SHIP_UUID), Query::HYDRATE_OBJECT);

        $ship = new DbShip(
            ID::makeNewID(DbShip::class),
            (string) time(),
            $starterShip
        );
        $this->entityManager->persist($ship);

        // new ships need to be put into a safe port
        $safePort = $this->getPortRepo()
            ->getARandomSafePort(Query::HYDRATE_OBJECT);

        $location = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            $safePort
        );

        $this->entityManager->persist($location);

        $this->entityManager->flush();
    }

    public function countAll(): int
    {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('count(1)');
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAll(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createShipMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }

    public function getByID(
        UuidInterface $uuid
    ): ?ShipEntity {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($results[0]);
    }

    public function moveShipToLocation(
        UuidInterface $shipId,
        UuidInterface $locationId
    ): void {
        $shipRepo = $this->getShipRepo();

        // fetch the ship and the port
        $ship = $shipRepo->getById($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $locationType = ID::getIDType($locationId);

        if ($locationType === DbPort::class) {
            $this->moveShipToPortId($ship, $locationId);
            return;
        }

        throw new \InvalidArgumentException('Invalid destination ID');
    }

    private function moveShipToPortId(
        DbShip $ship,
        UuidInterface $portId
    ) {
        $port = $this->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        if (!$port) {
            throw new \InvalidArgumentException('No such port');
        }

        // make a new ship location
        $newLocation = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            $port
        );
        $this->entityManager->persist($newLocation);

        // move all crates on the ship into the port
        // get the crates
        $crateLocations = $this->getCrateLocationRepo()
            ->findCurrentForShipID($ship->id, Query::HYDRATE_OBJECT);
        if (!empty($crateLocations)) {
            foreach($crateLocations as $crateLocation) {
                $newLocation = new DbCrateLocation(
                    ID::makeNewID(DbCrateLocation::class),
                    $crateLocation->crate,
                    $port,
                    null
                );
                $this->entityManager->persist($newLocation);
            }
        }
        $this->entityManager->flush();
    }
}
