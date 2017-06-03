<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;

class CrateLocationRepository extends EntityRepository
{
    public function queryCurrentLocationForCrateId(
        Uuid $crateId
    ): Query {
        $qb = $this->createQueryBuilder('tbl')
            ->where('IDENTITY(tbl.crate) = :crate')
            ->setParameter('crate', $crateId->getBytes())
        ;
        return $qb->getQuery();
    }
}
