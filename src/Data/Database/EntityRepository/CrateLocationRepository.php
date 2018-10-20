<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class CrateLocationRepository extends AbstractEntityRepository
{
    public function findWithCrateForPortIdAndUserId(
        UuidInterface $portId,
        UuidInterface $userId,
        $resultType = Query::HYDRATE_ARRAY
    ): array {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'c')
            ->join('tbl.crate', 'c')
            ->where('IDENTITY(tbl.port) = :portID')
            ->andWhere('tbl.isCurrent = true')
            ->andWhere('c.isDestroyed = false')
            ->andWhere('c.reservedFor IS NULL OR c.reservedFor = :userID')
            ->setParameter('portID', $portId->getBytes())
            ->setParameter('userID', $userId->getBytes())
            ->orderBy('c.value', 'DESC');
        return $qb->getQuery()->getResult($resultType);
    }

    public function findForCrateAndPortId(
        UuidInterface $crateId,
        UuidInterface $portId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port', 'crate')
            ->join('tbl.port', 'port')
            ->join('tbl.crate', 'crate')
            ->where('IDENTITY(tbl.crate) = :crate')
            ->andWhere('IDENTITY(tbl.port) = :port')
            ->andWhere('tbl.isCurrent = true')
            ->setMaxResults(1)
            ->setParameter('crate', $crateId->getBytes())
            ->setParameter('port', $portId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function findCurrentForShipID(
        UuidInterface $shipId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'crate')
            ->join('tbl.crate', 'crate')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('ship', $shipId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function makeInShip(Crate $crate, Ship $ship): void
    {
        $location = new CrateLocation(
            $crate,
            null,
            $ship
        );

        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

    public function makeInPort(Crate $crate, Port $port): void
    {
        $location = new CrateLocation(
            $crate,
            $port,
            null
        );

        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

    public function exitLocation(Crate $crate): void
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.crate) = :crate')
            ->andWhere('tbl.isCurrent = true')
            ->setParameter('crate', $crate->id->getBytes());

        $location = $qb->getQuery()->getSingleResult();
        $location->isCurrent = false;

        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

}
