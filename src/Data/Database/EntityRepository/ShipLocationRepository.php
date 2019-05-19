<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipLocation;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipLocationRepository extends AbstractEntityRepository implements CleanableInterface
{
    public function getCurrentForShipId(
        UuidInterface $shipId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port', 'channel', 'fromPort', 'toPort')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.toPort', 'toPort')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = true')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ship', $shipId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function getCurrentForShipIds(
        array $shipIds,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'port', 'channel')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.ship', 'ship')
            ->leftJoin('tbl.channel', 'channel')
            ->where('IDENTITY(tbl.ship) IN (:ships)')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('ships', $shipIds);
        return $qb->getQuery()->getResult($resultType);
    }

    public function getLatest(
        int $limit,
        int $offset = 0,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'port', 'channel', 'fromPort', 'toPort')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.ship', 'ship')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->leftJoin('channel.toPort', 'toPort')
            ->where('tbl.isCurrent = true')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('tbl.entryTime', 'DESC');
        return $qb->getQuery()->getResult($resultType);
    }

    public function getOldestExpired(
        DateTimeImmutable $since,
        int $limit = 1,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'channel', 'fromPort', 'toPort')
            ->leftJoin('tbl.ship', 'ship')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->leftJoin('channel.toPort', 'toPort')
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

    public function disableAllActiveForShipID(UuidInterface $uuid): void
    {
        $q = $this->getEntityManager()->createQuery(
            'UPDATE ' . ShipLocation::class . ' cl ' .
            'SET ' .
            'cl.isCurrent = false, ' .
            'cl.updatedAt = :time ' .
            'WHERE cl.isCurrent = true' .
            'AND IDENTITY(cl.ship) = :ship '
        );
        $q->setParameter('time', $this->dateTimeFactory->now());
        $q->setParameter('ship', $uuid->getBytes());
        $q->execute();
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
        $location->exitTime = $this->dateTimeFactory->now();
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
        Port $port
    ): void {
        $location = new ShipLocation(
            $ship,
            $port,
            null,
            $this->dateTimeFactory->now(),
        );
        $this->getEntityManager()->persist($location);

        $this->getEntityManager()->getEventRepo()->logShipArrival($ship, $port);

        $this->getEntityManager()->flush();
    }

    public function getActiveShipsForPortId(
        UuidInterface $portId,
        $resultType = Query::HYDRATE_ARRAY
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
            ->orderBy('ship.strength', 'DESC')
            ->setParameter('portId', $portId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function getInPortOfCapacity(
        DateTimeImmutable $before,
        int $capacity,
        int $limit,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'port', 'player', 'shipClass', 'rank')
            ->join('tbl.port', 'port')
            ->join('tbl.ship', 'ship')
            ->join('ship.owner', 'player')
            ->join('player.lastRankSeen', 'rank')
            ->join('ship.shipClass', 'shipClass')
            ->where('tbl.isCurrent = 1')
            ->andWhere('shipClass.capacity = :capacity')
            ->andWhere('tbl.entryTime <= :before')
            ->setMaxResults($limit)
            ->setParameter('before', $before)
            ->setParameter('capacity', $capacity);
        return $qb->getQuery()->getResult($resultType);
    }

    public function clean(\DateTimeImmutable $now): int
    {
        return $this->removeInactiveBefore($now);
    }
}
