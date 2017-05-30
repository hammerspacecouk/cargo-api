<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Crate;
use App\Domain\Entity\Crate as CrateEntity;
use Ramsey\Uuid\UuidInterface;

class CratesService extends AbstractService
{
    const CONTENTS = [
        'shoes',
        'bottles',
        'paper'
    ];

    // upon creation of the game, populate the database with 1 million crates

//    public function makeNew():void {
//        $this->activateFirstInactiveCrate();
//        return;
//
//        $crate = new Crate(self::CONTENTS[array_rand(self::CONTENTS)]);
//
//        $this->entityManager->persist($crate);
//        $this->entityManager->flush();
//    }
//
//    public function activateFirstInactiveCrate()
//    {
//        $qb = $this->getQueryBuilder(Crate::class)
//            ->where('status = :status')
//            ->setMaxResults(1)
//            ->setParameter('status', Crate::STATUS_INACTIVE)
//        ;
//
//        /** @var Crate $crate */
//        $crate = $qb->getQuery()->getFirstResult();
//        var_dump($crate);
//
//        $crate->status = Crate::STATUS_ACTIVE;
//
//        $this->entityManager->persist($crate);
//        $this->entityManager->flush();
//    }



    public function countAllAvailable()
    {
        $qb = $this->getQueryBuilder(Crate::class)
            ->select('count(1)')
            ->where('tbl.status != :inactiveStatus')
            ->setParameter('inactiveStatus', Crate::STATUS_INACTIVE)
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAvailable(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(Crate::class)
            ->where('tbl.status != :inactiveStatus')
            ->setParameter('inactiveStatus', Crate::STATUS_INACTIVE)
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createCrateMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function($result) use ($mapper) {
            return $mapper->getCrate($result);
        }, $results);
    }

    public function findByID(
        UuidInterface $uuid
    ): ?CrateEntity {
        $qb = $this->getQueryBuilder(Crate::class)
            ->where('tbl.id = :id')
            ->andWhere('tbl.status != :inactiveStatus')
            ->setParameter('id', $uuid->getBytes())
            ->setParameter('inactiveStatus', Crate::STATUS_INACTIVE)
        ;

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createCrateMapper();
        return $mapper->getCrate($results[0]);
    }
}
