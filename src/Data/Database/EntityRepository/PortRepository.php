<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PortRepository extends EntityRepository
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
}
