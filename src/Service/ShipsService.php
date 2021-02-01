<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\User as DbUser;
use App\Data\TokenProvider;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Token\Action\JoinConvoyToken;
use App\Domain\ValueObject\Token\Action\LeaveConvoyToken;
use App\Domain\ValueObject\Token\Action\SellShipToken;
use App\Domain\ValueObject\Transaction;
use App\Infrastructure\DateTimeFactory;
use Doctrine\DBAL\Driver\Result;
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
            ->addOrderBy('c.orderNumber', 'desc')
            ->addOrderBy('tbl.name', 'asc')
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

    public function getSellToken(Ship $ship): ?Transaction
    {
        if ($ship->isStarterShip()) {
            return null;
        }

        $earnings = $ship->calculateValue();

        $token = $this->tokenHandler->makeToken(...SellShipToken::make(
            $ship->getId(),
            $ship->getOwner()->getId(),
            $earnings
        ));
        $sellToken = new SellShipToken(
            $token,
            TokenProvider::getActionPath(SellShipToken::class)
        );
        return new Transaction(
            $earnings,
            $sellToken
        );
    }

    public function parseSellShipToken(
        string $tokenString
    ): SellShipToken {
        return new SellShipToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useSellShipToken(
        SellShipToken $token
    ): void {
        $userRepo = $this->entityManager->getUserRepo();

        /** @var DbShip $ship */
        $ship = $this->entityManager->getShipRepo()->getByID($token->getShipId(), Query::HYDRATE_OBJECT);
        /** @var DbUser $userEntity */
        $userEntity = $userRepo->getByID($token->getOwnerId(), Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function () use ($ship, $token, $userRepo, $userEntity) {

            // mark the ship as deleted
            $ship->deletedAt = DateTimeFactory::now();
            $ship->strength = 0;
            $this->entityManager->persist($ship);

            // save the player's new credits value
            $userRepo->updateScoreValue($userEntity, $token->getEarnings());

            $this->entityManager->flush();
            $this->tokenHandler->markAsUsed($token->getOriginalToken());
        });
    }

    public function getConvoyToken(Ship $currentShip, Ship $ship): JoinConvoyToken
    {
        $token = $this->tokenHandler->makeToken(...JoinConvoyToken::make(
            $currentShip->getId(),
            $currentShip->getOwner()->getId(),
            $ship->getId()
        ));
        return new JoinConvoyToken(
            $token,
            TokenProvider::getActionPath(JoinConvoyToken::class)
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
            $token,
            TokenProvider::getActionPath(LeaveConvoyToken::class)
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
            $this->entityManager->getUserAchievementRepo()->recordMakeConvoy($token->getOwnerId());
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
        $this->entityManager->transactional(function () use ($token) {
            $this->entityManager->getShipRepo()->leaveConvoy($token->getCurrentShipId());
            $this->tokenHandler->markAsUsed($token->getOriginalToken());
        });
    }

    public function reduceHealthOfInfected(float $newPercent): void
    {
        $query = <<<SQL
            UPDATE ships
            SET strength = (GREATEST(1, FLOOR(strength * :percent)))
            WHERE strength > 1
            AND has_plague = 1;
        SQL;
        $result = $this->entityManager->getConnection()->executeQuery($query, ['percent' => $newPercent,]);
        if ($result instanceof Result) {
            $this->logger->notice('[PLAGUE] ' . $result->rowCount() . ' infected lost health');
        }
    }

    public function randomOutbreak(): void
    {
        if (random_int(1, 250) !== 1) {
            return;
        }

        $ship = $this->entityManager->getShipRepo()->infectRandomShip();
        if ($ship) {
            $this->logger->notice('[PLAGUE] New Outbreak occurred on ' . $ship->name);
        }
    }

    public function quickEditShip(UuidInterface $shipId, array $fields): void
    {
        /** @var DbUser $shipEntity */
        $shipEntity = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);

        foreach ($fields as $key => $value) {
            $shipEntity->{$key} = $value === '' ? null : $value;
        }

        $this->entityManager->persist($shipEntity);
        $this->entityManager->flush();
    }
}
