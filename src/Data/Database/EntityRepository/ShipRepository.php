<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Ship;
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
            ->setParameter('ownerId', $ownerId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function renameShip(
        UuidInterface $shipId,
        string $newName
    ): void {
        /** @var Ship $ship */
        $ship = $this->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        // update the ship name
        $ship->name = $newName;
        $this->getEntityManager()->persist($ship);
        $this->getEntityManager()->flush();
    }
}
