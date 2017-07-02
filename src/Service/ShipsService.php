<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Channel as DbChannel;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\ID;
use App\Domain\Entity\Ship as ShipEntity;
use App\Domain\Exception\IllegalMoveException;
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
            $safePort,
            null
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

    public function getByIDWithLocation(
        UuidInterface $uuid
    ): ?ShipEntity {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$result) {
            return null;
        }

        $result['location'] = $this->getShipLocationRepo()
            ->getCurrentForShipID($uuid);

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($result);
    }

    public function moveShipToLocation(
        UuidInterface $shipId,
        UuidInterface $locationId
    ): void {
        $shipRepo = $this->getShipRepo();

        // fetch the ship
        $ship = $shipRepo->getById($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $locationType = ID::getIDType($locationId);

        // fetch the ships current location
        $currentShipLocation = $this->getShipLocationRepo()
            ->getCurrentForShipID($ship->id, Query::HYDRATE_OBJECT);

        if ($locationType === DbPort::class) {
            // if the current location is a port this is an Illegal move
            if ($currentShipLocation->port) {
                throw new IllegalMoveException('You can only move into a port if you came from a channel');
            }
            $this->moveShipToPortId($ship, $currentShipLocation, $locationId);
            return;
        } elseif ($locationType === DbChannel::class) {
            // if the current location is a port this is an Illegal move
            if ($currentShipLocation->channel) {
                throw new IllegalMoveException('You can only move into a channel if you came from a port');
            }
            $this->moveShipToChannelId($ship, $currentShipLocation, $locationId);
            return;
        }

        throw new \InvalidArgumentException('Invalid destination ID');
    }

    private function moveShipToPortId(
        DbShip $ship,
        DbShipLocation $currentShipLocation,
        UuidInterface $portId
    ) {
        $port = $this->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        if (!$port) {
            throw new \InvalidArgumentException('No such port');
        }

        // remove the old ship location
        $currentShipLocation->isCurrent = false;
        $this->entityManager->persist($currentShipLocation);

        // make a new ship location
        $newLocation = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            $port,
            null
        );
        $this->entityManager->persist($newLocation);

        // move all crates on the ship into the port
        // get the crates
        $crateLocations = $this->getCrateLocationRepo()
            ->findCurrentForShipID($ship->id, Query::HYDRATE_OBJECT);
        if (!empty($crateLocations)) {
            foreach ($crateLocations as $crateLocation) {
                $crateLocation->isCurrent = false;
                $this->entityManager->persist($crateLocation);

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

    private function moveShipToChannelId(
        DbShip $ship,
        DbShipLocation $currentShipLocation,
        UuidInterface $channelId
    ) {
        $channel = $this->getChannelRepo()->getByID($channelId, Query::HYDRATE_OBJECT);
        if (!$channel) {
            throw new \InvalidArgumentException('No such channel');
        }

        // remove the old ship location
        $currentShipLocation->isCurrent = false;
        $this->entityManager->persist($currentShipLocation);

        // make a new ship location
        $newLocation = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            null,
            $channel
        );
        $this->entityManager->persist($newLocation);
        $this->entityManager->flush();
    }
}
