<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipLocation;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\User;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipLocationRepository extends AbstractEntityRepository implements CleanableInterface
{
    /**
     * @param UuidInterface $shipId
     * @param int $resultType
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCurrentForShipId(
        UuidInterface $shipId,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port', 'channel', 'fromPort', 'toPort', 'blockadedBy')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.toPort', 'toPort')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->leftJoin('port.blockadedBy', 'blockadedBy')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = true')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ship', $shipId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function sumDeltaForUserId(
        UuidInterface $userId
    ): int {
        $qb = $this->createQueryBuilder('tbl')
            ->select('SUM(tbl.scoreDelta)')
            ->join('tbl.ship', 'ship')
            ->where('IDENTITY(ship.owner) = :userId')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('userId', $userId->getBytes());
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $shipIds
     * @param int $resultType
     * @return mixed
     */
    public function getCurrentForShipIds(
        array $shipIds,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'port', 'channel', 'fromPort', 'toPort')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.ship', 'ship')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.toPort', 'toPort')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->where('IDENTITY(tbl.ship) IN (:ships)')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('ships', $shipIds);
        return $qb->getQuery()->getResult($resultType);
    }

    /**
     * @param DateTimeImmutable $since
     * @param int $limit
     * @param int $resultType
     * @return mixed
     */
    public function getOldestExpired(
        DateTimeImmutable $since,
        int $limit = 1,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'channel', 'shipClass', 'fromPort', 'toPort')
            ->leftJoin('tbl.ship', 'ship')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->leftJoin('channel.toPort', 'toPort')
            ->leftJoin('ship.shipClass', 'shipClass')
            ->where('tbl.isCurrent = true')
            ->andWhere('tbl.exitTime <= :now')
            ->orderBy('tbl.exitTime', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('now', $since);
        return $qb->getQuery()->getResult($resultType);
    }

    public function removeInactiveBefore(DateTimeImmutable $before): int
    {
        $entity = ShipLocation::class;
        $sql = "DELETE FROM $entity t WHERE t.isCurrent = 0 AND t.exitTime < :beforeTime";
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('beforeTime', $before);
        return $query->execute();
    }

    public function exitLocation(Ship $ship): void
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship')
            ->join('tbl.ship', 'ship')
            ->leftJoin('tbl.port', 'port')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('ship', $ship->id->getBytes());

        $location = $qb->getQuery()->getSingleResult();
        $location->exitTime = DateTimeFactory::now();
        $location->isCurrent = false;

        if ($location->port) {
            $this->getEntityManager()->getEventRepo()->logShipDeparture($ship, $location->port);
        }

        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

    public function makeInChannel(
        Ship $ship,
        Channel $channel,
        DateTimeImmutable $entryTime,
        DateTimeImmutable $exitTime,
        bool $reverseDirection,
        int $scoreDelta
    ): void {
        $location = new ShipLocation(
            $ship,
            null,
            $channel,
            $entryTime,
        );

        $location->scoreDelta = $scoreDelta;
        $location->exitTime = $exitTime;
        $location->reverseDirection = $reverseDirection;
        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

    public function makeInPort(
        Ship $ship,
        Port $port,
        bool $initialLaunch = false
    ): void {
        $location = new ShipLocation(
            $ship,
            $port,
            null,
            DateTimeFactory::now(),
        );
        $this->getEntityManager()->persist($location);

        if (!$initialLaunch) {
            $this->getEntityManager()->getEventRepo()->logShipArrival($ship, $port);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param UuidInterface $portId
     * @param int $resultType
     * @return mixed
     */
    public function getActiveShipsForPortId(
        UuidInterface $portId,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'port', 'player', 'shipClass', 'rank')
            ->join('tbl.ship', 'ship')
            ->join('ship.owner', 'player')
            ->join('tbl.port', 'port')
            ->join('player.lastRankSeen', 'rank')
            ->join('ship.shipClass', 'shipClass')
            ->where('IDENTITY(tbl.port) = :portId')
            ->andWhere('tbl.isCurrent = 1')
            ->andWhere('ship.strength > 0')
            ->orderBy('shipClass.isDefenceShip', 'DESC')
            ->addOrderBy('ship.strength', 'DESC')
            ->setParameter('portId', $portId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function getProbesThatArrivedInPortBeforeTime(
        DateTimeImmutable $before,
        int $limit,
        int $resultType = Query::HYDRATE_ARRAY
    ): mixed {
        $qb = $this->createQueryBuilder('tbl');
        $qb = $qb->select('tbl', 'ship', 'port', 'player', 'shipClass', 'rank')
            ->join('tbl.port', 'port')
            ->join('tbl.ship', 'ship')
            ->join('ship.owner', 'player')
            ->join('player.lastRankSeen', 'rank')
            ->join('ship.shipClass', 'shipClass')
            ->where('tbl.isCurrent = 1')
            ->andWhere('ship.strength > 0')
            ->andWhere('shipClass.autoNavigate = true')
            ->andWhere('tbl.entryTime <= :before')
            ->andWhere($qb->expr()->orX(
                'player.permissionLevel >= :permission',
                'rank.threshold < :trialThreshold'
            ))
            ->andWhere('rank.threshold < :maxRank')
            ->orderBy('tbl.entryTime', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('before', $before)
            ->setParameter('permission', User::PERMISSION_FULL)
            ->setParameter('trialThreshold', PlayerRank::TRIAL_END_THRESHOLD)
            ->setParameter('maxRank', 1000);
        return $qb->getQuery()->getResult($resultType);
    }

    public function clean(DateTimeImmutable $now): int
    {
        return $this->removeInactiveBefore($now->sub(new DateInterval('P200D')));
    }

    public function getRecentForShipID(UuidInterface $shipId, int $limit): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port')
            ->join('tbl.port', 'port')
            ->where('IDENTITY(tbl.ship) = :shipId')
            ->andWhere('tbl.port IS NOT NULL')
            ->orderBy('tbl.entryTime', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('shipId', $shipId->getBytes());
        return $qb->getQuery()->getArrayResult();
    }
}
