<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class CrateLocationRepository extends EntityRepository
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
            ->setParameter('crate', $crateId->getBytes())
        ;
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function findCurrentForShipID(
        UuidInterface $crateId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'crate')
            ->leftJoin('tbl.crate', 'crate')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->setParameter('ship', $crateId->getBytes())
        ;
        return $qb->getQuery()->getResult($resultType);
    }
}
