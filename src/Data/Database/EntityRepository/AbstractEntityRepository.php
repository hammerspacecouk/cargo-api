<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractEntityRepository extends EntityRepository
{
    public function getByID(
        UuidInterface $uuid,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
