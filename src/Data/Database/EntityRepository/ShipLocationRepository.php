<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipLocation;
use App\Data\ID;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipLocationRepository extends AbstractEntityRepository implements CleanableInterface
{
    public function getCurrentForShipId(
        UuidInterface $shipId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl');
        $qb->select('tbl', 'port', 'channel', 'fromPort', 'toPort')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.channel', 'channel')
            ->leftJoin('channel.toPort', 'toPort')
            ->leftJoin('channel.fromPort', 'fromPort')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = :current')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('current', true)
            ->setParameter('ship', $shipId);
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
            ->andWhere('tbl.isCurrent = :current')
            ->setParameter('current', true)
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
            ->where('tbl.isCurrent = :current')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('tbl.entryTime', 'DESC')
            ->setParameter('current', true);
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
            ->where('tbl.isCurrent = :current')
            ->andWhere('tbl.exitTime <= :now')
            ->orderBy('tbl.exitTime', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('current', true)
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
            'cl.isCurrent = :false, ' .
            'cl.updatedAt = :time ' .
            'WHERE cl.isCurrent = :current' .
            'AND IDENTITY(cl.ship) = :ship '
        );
        $q->setParameter('current', true);
        $q->setParameter('false', false);
        $q->setParameter('time', $this->currentTime);
        $q->setParameter('ship', $uuid);
        $q->execute();
    }

    public function exitLocation(Ship $ship): void
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship')
            ->leftJoin('tbl.ship', 'ship')
            ->where('IDENTITY(tbl.ship) = (:ship)')
            ->andWhere('tbl.isCurrent = :current')
            ->setParameter('current', true)
            ->setParameter('ship', $ship->id);

        $location = $qb->getQuery()->getSingleResult();
        $location->exitTime = $this->currentTime;
        $location->isCurrent = false;

        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

    public function makeInChannel(
        Ship $ship,
        Channel $channel,
        DateTimeImmutable $exitTime,
        bool $reverseDirection
    ): void {
        $channel = new ShipLocation(
            ID::makeNewID(ShipLocation::class),
            $ship,
            null,
            $channel,
            $this->currentTime
        );

        $channel->exitTime = $exitTime;
        $channel->reverseDirection = $reverseDirection;
        $this->getEntityManager()->persist($channel);
        $this->getEntityManager()->flush();
    }

    public function getShipsForPortId(UuidInterface $portId)
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'ship', 'player')
            ->innerJoin('tbl.ship', 'ship')
            ->innerJoin('ship.owner', 'player')
            ->where('IDENTITY(tbl.port) = :portId')
            ->andWhere('tbl.isCurrent = :current')
            ->setParameter('current', true)
            ->setParameter('portId', $portId);
        return array_map(function (array $result) {
            return $result['ship'];
        }, $qb->getQuery()->getArrayResult());
    }

    public function clean(\DateTimeImmutable $now): int
    {
        return $this->removeInactiveBefore($now);
    }
}
