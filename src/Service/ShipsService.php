<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Data\TokenProvider;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Token\Action\JoinConvoyToken;
use App\Domain\ValueObject\Token\Action\LeaveConvoyToken;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipsService extends AbstractService
{
    public function countAll(): int
    {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('count(1)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $limit
     * @param int $page
     * @return Ship[]
     */
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
        return array_map(static function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }

    public function getByID(
        UuidInterface $uuid
    ): ?Ship {

        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c', 'o', 'r')
            ->join('tbl.shipClass', 'c')
            ->join('tbl.owner', 'o')
            ->join('o.lastRankSeen', 'r')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes());

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($result);
    }

    public function findAllInConvoy(UuidInterface $convoyId): array
    {
        $shipsInConvoy = $this->entityManager->getShipRepo()->getByConvoyID($convoyId);
        $mapper = $this->mapperFactory->createShipMapper();

        return array_map(static function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $shipsInConvoy);
    }

    public function shipOwnedBy(
        UuidInterface $shipId,
        UuidInterface $ownerId
    ): bool {
        return (bool)$this->getByIDForOwnerId($shipId, $ownerId);
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
            ->select('tbl', 'c', 'o', 'r')
            ->join('tbl.shipClass', 'c')
            ->join('tbl.owner', 'o')
            ->join('o.lastRankSeen', 'r')
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

    /**
     * @param UuidInterface $userId
     * @param int $limit
     * @param int $page
     * @return Ship[]
     */
    public function getForOwnerIDWithLocation(
        UuidInterface $userId,
        int $limit = 1000,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbShip::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('IDENTITY(tbl.owner) = :id')
            ->orderBy('tbl.convoyUuid', 'desc')
            ->addOrderBy('tbl.strength', 'desc')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
            ->setParameter('id', $userId->getBytes());

        $results = $qb->getQuery()->getArrayResult();
        $results = $this->attachLocationToShips($results);

        return $this->mapMany($results);
    }

    /**
     * @param Port $port
     * @return Ship[]
     */
    public function findAllActiveInPort(Port $port): array
    {
        $results = $this->entityManager->getShipLocationRepo()->getActiveShipsForPortId($port->getId());
        $mapper = $this->mapperFactory->createShipMapper();
        return array_map(static function ($result) use ($mapper) {
            return $mapper->getShip($result['ship']);
        }, $results);
    }

    /**
     * @param array[] $ships
     * @return array[]
     */
    private function attachLocationToShips(array $ships): array
    {
        if (empty($ships)) {
            return $ships;
        }

        // get all the IDs
        $ids = array_map(static function ($ship) {
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

    /**
     * @param array[] $results
     * @return Ship[]
     */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createShipMapper();
        return array_map(static function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }

    public function getConvoyToken(Ship $currentShip, Ship $ship): JoinConvoyToken
    {
        $token = $this->tokenHandler->makeToken(...JoinConvoyToken::make(
            $currentShip->getId(),
            $currentShip->getOwner()->getId(),
            $ship->getId()
        ));
        return new JoinConvoyToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(JoinConvoyToken::class, $this->dateTimeFactory->now())
        );
    }

    public function getLeaveConvoyToken(Ship $ship): ?LeaveConvoyToken
    {
        if (!$ship->isInConvoy()) {
            return null;
        }

        $token = $this->tokenHandler->makeToken(...LeaveConvoyToken::make(
            $ship->getId(),
            $ship->getOwner()->getId(),
        ));
        return new LeaveConvoyToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(LeaveConvoyToken::class, $this->dateTimeFactory->now())
        );
    }

    public function parseJoinConvoyToken(
        string $tokenString
    ): JoinConvoyToken {
        return new JoinConvoyToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useJoinConvoyToken(
        JoinConvoyToken $token
    ): void {
        // get the two ships
        /** @var DbShip $currentShip */
        $currentShip = $this->entityManager->getShipRepo()->getByID($token->getCurrentShipId(), Query::HYDRATE_OBJECT);
        /** @var DbShip $targetShip */
        $targetShip = $this->entityManager->getShipRepo()->getByID($token->getChosenShipId(), Query::HYDRATE_OBJECT);

        if (!$targetShip->convoyUuid) {
            $targetShip->convoyUuid = $this->uuidFactory->uuid6();
        }

        $currentShip->convoyUuid = $targetShip->convoyUuid;
        $this->entityManager->persist($targetShip);
        $this->entityManager->persist($currentShip);

        $this->entityManager->transactional(function () use ($token) {
            $this->entityManager->flush();
            $this->tokenHandler->markAsUsed($token->getOriginalToken());
        });
    }

    public function parseLeaveConvoyToken(
        string $tokenString
    ): LeaveConvoyToken {
        return new LeaveConvoyToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useLeaveConvoyToken(LeaveConvoyToken $token): void
    {
        // get the ship
        /** @var DbShip $currentShip */
        $currentShip = $this->entityManager->getShipRepo()->getByID($token->getCurrentShipId(), Query::HYDRATE_OBJECT);

        $currentConvoy = $currentShip->convoyUuid;
        if ($currentConvoy === null) {
            return;
        }

        /** @var DbShip[] $shipsInConvoy */
        $shipsInConvoy = $this->entityManager->getShipRepo()->getByConvoyID($currentConvoy, Query::HYDRATE_OBJECT);

        if (count($shipsInConvoy) <= 2) {
            foreach ($shipsInConvoy as $ship) {
                $ship->convoyUuid = null;
                $this->entityManager->persist($ship);
            }
        } else {
            $currentShip->convoyUuid = null;
            $this->entityManager->persist($currentShip);
        }

        $this->entityManager->transactional(function () use ($token) {
            $this->entityManager->flush();
            $this->tokenHandler->markAsUsed($token->getOriginalToken());
        });
    }
}
