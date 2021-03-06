<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipLocation;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Ramsey\Uuid\UuidInterface;

class CrateLocationRepository extends AbstractEntityRepository implements CleanableInterface
{

    public function findWithCrateForPortId(
        UuidInterface $portId,
        int $limit = 10,
        int $resultType = Query::HYDRATE_ARRAY
    ): array {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'c')
            ->join('tbl.crate', 'c')
            ->where('IDENTITY(tbl.port) = :portID')
            ->andWhere('tbl.isCurrent = true')
            ->andWhere('c.isDestroyed = false')
            ->andWhere('c.reservedFor IS NULL')
            ->setParameter('portID', $portId->getBytes())
            ->setMaxResults($limit)
            ->orderBy('c.isGoal', 'DESC')
            ->addOrderBy('c.value', 'DESC');
        return $qb->getQuery()->getResult($resultType);
    }

    public function findReservedWithCrateForPortIdAndUserId(
        UuidInterface $portId,
        UuidInterface $userId,
        int $limit = 10,
        int $resultType = Query::HYDRATE_ARRAY
    ): array {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'c')
            ->join('tbl.crate', 'c')
            ->where('IDENTITY(tbl.port) = :portID')
            ->andWhere('tbl.isCurrent = true')
            ->andWhere('c.isDestroyed = false')
            ->andWhere('c.reservedFor = :userID')
            ->setParameter('portID', $portId->getBytes())
            ->setParameter('userID', $userId->getBytes())
            ->setMaxResults($limit)
            ->orderBy('c.isGoal', 'DESC')
            ->addOrderBy('c.value', 'DESC');
        return $qb->getQuery()->getResult($resultType);
    }

    /**
     * @param UuidInterface $crateId
     * @param UuidInterface $portId
     * @param int $resultType
     * @return mixed
     */
    public function findForCrateAndPortId(
        UuidInterface $crateId,
        UuidInterface $portId,
        int $resultType = Query::HYDRATE_ARRAY
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

    /**
     * @param UuidInterface $shipId
     * @param int $resultType
     * @return mixed
     */
    public function findCurrentForShipID(
        UuidInterface $shipId,
        int $resultType = Query::HYDRATE_ARRAY
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

    /**
     * @param UuidInterface $shipId
     * @param int $resultType
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findMostRecentForShipID(
        UuidInterface $shipId,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ship', $shipId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    /**
     * @param DateTimeImmutable $before
     * @param int $limit
     * @param int $resultType
     * @return mixed
     */
    public function getOnShipsInPortBefore(
        DateTimeImmutable $before,
        int $limit,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'crate', 'ship', 'owner')
            ->join('tbl.crate', 'crate')
            ->join('tbl.ship', 'ship')
            ->join('ship.owner', 'owner')
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

    /**
     * @return mixed
     */
    public function findPortWithOldestGoalCrate(int $resultType = Query::HYDRATE_ARRAY)
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port')
            ->join('tbl.port', 'port')
            ->join('tbl.crate', 'crate')
            ->where('crate.isGoal = true')
            ->andWhere('tbl.isCurrent = true')
            ->orderBy('tbl.createdAt', 'ASC')
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function findGoalCrates(int $limit): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port')
            ->join('tbl.port', 'port')
            ->join('tbl.crate', 'crate')
            ->where('crate.isGoal = true')
            ->andWhere('tbl.isCurrent = true')
            ->orderBy('tbl.createdAt', 'ASC')
            ->groupBy('port.id')
            ->setMaxResults($limit);
        return $qb->getQuery()->getArrayResult();
    }

    public function findLostCrates(int $resultType = Query::HYDRATE_ARRAY): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->leftJoin('tbl.ship', 'ship')
            ->where('ship.id IS NULL')
            ->andWhere('tbl.port IS NULL')
            ->andWhere('tbl.isCurrent = true');
        return $qb->getQuery()->getResult($resultType);
    }

    /**
     * @return mixed
     */
    public function findPreviousForCrateId(UuidInterface $id, int $resultType = Query::HYDRATE_ARRAY)
    {
        $qb = $this->createQueryBuilder('tbl')
            ->where('IDENTITY(tbl.crate) = :crateId')
            ->andWhere('tbl.ship IS NULL')
            ->andWhere('tbl.isCurrent = false')
            ->orderBy('tbl.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('crateId', $id->getBytes());
        return $qb->getQuery()->getSingleResult($resultType);
    }

    public function removeInactiveBefore(DateTimeImmutable $before): int
    {
        $entity = CrateLocation::class;
        $sql = "DELETE FROM $entity t WHERE t.isCurrent = 0 AND t.updatedAt < :beforeTime";
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('beforeTime', $before);
        return $query->execute();
    }

    public function clean(DateTimeImmutable $now): int
    {
        return $this->removeInactiveBefore($now->sub(new DateInterval('P14D')));
    }
}
