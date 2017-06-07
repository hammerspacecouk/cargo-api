<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipLocationRepository extends EntityRepository
{
    public function getCurrentForShipID(
        UuidInterface $shipId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->where('IDENTITY(tbl.ship) = :ship')
            ->orderBy('tbl.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ship', $shipId->getBytes())
        ;
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
