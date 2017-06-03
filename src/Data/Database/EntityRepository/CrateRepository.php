<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class CrateRepository extends EntityRepository
{
    public function queryByID(
        UuidInterface $uuid
    ): Query {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;

        return $qb->getQuery();
    }

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
