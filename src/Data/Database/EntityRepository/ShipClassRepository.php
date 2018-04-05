<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;

class ShipClassRepository extends AbstractEntityRepository
{
    public function getStarter(
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.isStarterShip = :true')
            ->setMaxResults(1)
            ->setParameter('true', true);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
