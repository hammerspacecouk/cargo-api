<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipLocation;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Ramsey\Uuid\UuidInterface;

class CrateLocationRepository extends AbstractEntityRepository
{
    public function findWithCrateForPortIdAndUserId(
        UuidInterface $portId,
        UuidInterface $userId,
        int $limit = 10,
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
            ->setMaxResults($limit)
            ->orderBy('c.isGoal', 'DESC')
            ->addOrderBy('c.value', 'DESC');
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
            ->orderBy('crate.isGoal', 'ASC')
            ->addOrderBy('crate.value', 'ASC')
            ->setParameter('ship', $shipId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function findMostRecentForShipID(
        UuidInterface $shipId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ship', $shipId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function getOnShipsInPortBefore(
        DateTimeImmutable $before,
        int $limit,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'crate', 'ship')
            ->join('tbl.crate', 'crate')
            ->join('tbl.ship', 'ship')
            ->join(
                ShipLocation::class,
                'shipLoc',
                Join::WITH,
                'ship.id = shipLoc.ship'
            )
            ->join('shipLoc.port', 'port')
            ->where('tbl.updatedAt <= :before')
            ->andWhere('tbl.isCurrent = true')
            ->andWhere('shipLoc.isCurrent = true')
            ->setMaxResults($limit)
            ->setParameter('before', $before);
        return $qb->getQuery()->getResult($resultType);
    }

    public function makeInShip(Crate $crate, Ship $ship): void
    {
        $location = new CrateLocation(
            $crate,
            null,
            $ship,
        );

        $this->getEntityManager()->persist($location);
        $this->getEntityManager()->flush();
    }

    public function makeInPort(Crate $crate, Port $port): void
    {
        $location = new CrateLocation(
            $crate,
            $port,
            null,
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
