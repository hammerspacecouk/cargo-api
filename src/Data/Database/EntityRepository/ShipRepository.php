<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipRepository extends AbstractEntityRepository
{
    public function getShipForOwnerId(
        UuidInterface $shipId,
        UuidInterface $ownerId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->andWhere('IDENTITY(tbl.owner) = :ownerId')
            ->setParameter('id', $shipId->getBytes())
            ->setParameter('ownerId', $ownerId->getBytes())
        ;
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
