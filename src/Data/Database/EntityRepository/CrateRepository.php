<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\CrateLocation;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Ramsey\Uuid\UuidInterface;

class CrateRepository extends AbstractEntityRepository
{
    public function getActiveByID(
        UuidInterface $uuid,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->innerJoin(CrateLocation::class, 'location', Join::WITH, 'location.crate = tbl')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
