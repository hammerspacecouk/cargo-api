<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipClass;
use App\Domain\Entity\Ship as ShipEntity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipsService extends AbstractService
{
    const STARTER_SHIP_UUID = 'c274d46f-5b3b-433c-81a8-ac9f97247699';

    public function makeNew():void
    {
        $starterShip = $this->entityManager->getRepository(ShipClass::class)
            ->queryByID(Uuid::fromString(self::STARTER_SHIP_UUID))
            ->getOneOrNullResult();

        $crate = new Ship((string) time(), $starterShip);

        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

    public function countAll()
    {
        $qb = $this->getQueryBuilder(Ship::class)
            ->select('count(1)');
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAll(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(Ship::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createShipMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getShip($result);
        }, $results);
    }

    public function findByID(
        UuidInterface $uuid
    ): ?ShipEntity {
        $qb = $this->getQueryBuilder(Ship::class)
            ->select('tbl', 'c')
            ->join('tbl.shipClass', 'c')
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createShipMapper();
        return $mapper->getShip($results[0]);
    }
}
