<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\ApplicationTime;
use App\Data\Database\Entity\ShipLocation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipLocationRepository extends EntityRepository
{
    public function getCurrentForShipId(
        UuidInterface $shipId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port', 'channel')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.channel', 'channel')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = true')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ship', $shipId->getBytes())
        ;
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
            ->setParameter('ships', $shipIds)
        ;
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
            ->orderBy('tbl.entryTime', 'DESC')
        ;
        return $qb->getQuery()->getResult($resultType);
    }

    public function disableAllActiveForShipID(UuidInterface $uuid): void
    {
        // remove any old crate locations
        $q = $this->getEntityManager()->createQuery(
            'UPDATE ' . ShipLocation::class . ' cl ' .
            'SET ' .
            'cl.isCurrent = false, ' .
            'cl.updatedAt = :time ' .
            'WHERE IDENTITY(cl.ship) = :ship ' .
            'AND cl.isCurrent = true'
        );
        $q->setParameter('time', ApplicationTime::getTime());
        $q->setParameter('ship', $uuid->getBytes());
        $q->execute();
    }
}
