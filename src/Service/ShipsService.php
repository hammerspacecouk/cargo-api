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
use App\Domain\Entity\User;
use App\Domain\Exception\IllegalMoveException;
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
            ID::makeNewID(DbShip::class),
            $this->entityManager->getDictionaryRepo()->getRandomShipName(),
            $starterShip,
            $user
        );
        $this->entityManager->persist($ship);

        // new ships need to be put into a safe port
        $safePort = $this->entityManager->getPortRepo()
            ->getARandomSafePort(Query::HYDRATE_OBJECT);

        $location = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            $safePort,
            null,
            $this->currentTime
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
        $this->logger->info(__CLASS__ . ':' . __METHOD__);

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

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($result);
    }

    public function getByIDForOwnerId(
        UuidInterface $shipId,
        UuidInterface $ownerId
    ): ?ShipEntity {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->andWhere('IDENTITY(tbl.owner) = :ownerId')
            ->setParameter('id', $shipId->getBytes())
            ->setParameter('ownerId', $ownerId->getBytes())
        ;

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
            ->setParameter('id', $userId->getBytes())
        ;
        return (int) $qb->getQuery()->getSingleScalarResult();
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
            ->setParameter('id', $userId->getBytes())
        ;

        $results = $qb->getQuery()->getArrayResult();
        $results = $this->attachLocationToShips($results);

        $mapper = $this->mapperFactory->createShipMapper();

        return array_map(function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }

    public function moveShipToLocation(
        UuidInterface $shipId,
        UuidInterface $locationId
    ): void {
        $shipRepo = $this->entityManager->getShipRepo();

        // fetch the ship
        $ship = $shipRepo->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $locationType = ID::getIDType($locationId);

        // fetch the ships current location
        $currentShipLocation = $this->entityManager->getShipLocationRepo()
            ->getCurrentForShipId($ship->id, Query::HYDRATE_OBJECT);

        if ($locationType === DbPort::class) {
            // if the current location is a port this is an Illegal move
            if ($currentShipLocation->port) {
                throw new IllegalMoveException('You can only move into a port if you came from a channel');
            }
            $this->moveShipToPortId($ship, $currentShipLocation, $locationId);
            return;
        }

        if ($locationType === DbChannel::class) {
            // if the current location is a port this is an Illegal move
            if ($currentShipLocation->channel) {
                throw new IllegalMoveException('You can only move into a channel if you came from a port');
            }
            $this->moveShipToChannelId($ship, $currentShipLocation, $locationId);
            return;
        }

        throw new \InvalidArgumentException('Invalid destination ID');
    }

    public function requestShipName(
        UuidInterface $userId,
        UuidInterface $shipId
    ): string {

        // check the ship exists and belongs to the user
        if (!$this->entityManager->getShipRepo()->getShipForOwnerId($shipId, $userId)) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        // todo - check the user has enough credits

        // todo -deduct the user credits

        // todo - should it check to see if it already exists?

        return $this->entityManager->getDictionaryRepo()->getRandomShipName();
    }


    private function moveShipToPortId(
        DbShip $ship,
        DbShipLocation $currentShipLocation,
        UuidInterface $portId
    ) {
        $port = $this->entityManager->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        if (!$port) {
            throw new \InvalidArgumentException('No such port');
        }

        // remove the old ship location
        $currentShipLocation->isCurrent = false;
        $currentShipLocation->exitTime = $this->currentTime;
        $this->entityManager->persist($currentShipLocation);

        // make a new ship location
        $newLocation = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            $port,
            null,
            $this->currentTime
        );
        $this->entityManager->persist($newLocation);

        // move all crates on the ship into the port
        // get the crates
        $crateLocations = $this->entityManager->getCrateLocationRepo()
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
        $channel = $this->entityManager->getChannelRepo()->getByID($channelId, Query::HYDRATE_OBJECT);
        if (!$channel) {
            throw new \InvalidArgumentException('No such channel');
        }

        // remove the old ship location
        $currentShipLocation->isCurrent = false;
        $currentShipLocation->exitTime = $this->currentTime;
        $this->entityManager->persist($currentShipLocation);

        // make a new ship location
        $newLocation = new DbShipLocation(
            ID::makeNewID(DbShipLocation::class),
            $ship,
            null,
            $channel,
            $this->currentTime
        );
        $this->entityManager->persist($newLocation);
        $this->entityManager->flush();
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
}
