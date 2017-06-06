<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class CrateRepository extends AbstractEntityRepository
{

    public function queryActiveByID(
        UuidInterface $uuid
    ): Query {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.id = :id')
            ->andWhere('tbl.status != :inactiveStatus')
            ->setParameter('id', $uuid->getBytes())
            ->setParameter('inactiveStatus', Crate::STATUS_INACTIVE)
        ;

        return $qb->getQuery();
    }
}
