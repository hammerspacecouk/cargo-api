<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\CrateLocation;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class CrateLocationRepository extends AbstractEntityRepository
{
    public function getCurrentForCrateID(
        UuidInterface $crateId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'port', 'ship')
            ->leftJoin('tbl.port', 'port')
            ->leftJoin('tbl.ship', 'ship')
            ->where('IDENTITY(tbl.crate) = :crate')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('crate', $crateId);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function findCurrentForCreateID(
        UuidInterface $crateId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.id = :id')
            ->andWhere('tbl.isCurrent = :true')
            ->setParameter('true', true)
            ->setParameter('id', $crateId);
        return $qb->getQuery()->getResult($resultType);
    }

    public function findCurrentForShipID(
        UuidInterface $crateId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'crate')
            ->leftJoin('tbl.crate', 'crate')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->andWhere('tbl.isCurrent = :true')
            ->setParameter('true', true)
            ->setParameter('ship', $crateId);
        return $qb->getQuery()->getResult($resultType);
    }

    public function disableAllActiveForCrateID(UuidInterface $uuid): void
    {
        // remove any old crate locations
        $q = $this->getEntityManager()->createQuery(
            'UPDATE ' . CrateLocation::class . ' cl ' .
            'SET ' .
            'cl.isCurrent = :false, ' .
            'cl.updatedAt = :time ' .
            'WHERE IDENTITY(cl.crate) = :crate ' .
            'AND cl.isCurrent = :true'
        );
        $q->setParameter('true', true);
        $q->setParameter('false', false);
        $q->setParameter('time', $this->currentTime);
        $q->setParameter('crate', $uuid);
        $q->execute();
    }
}
