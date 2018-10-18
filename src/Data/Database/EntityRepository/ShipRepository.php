<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipClass;
use App\Data\Database\Entity\User;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
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

        $oldName = $ship->name;
        // update the ship name
        $ship->name = $newName;
        $this->getEntityManager()->persist($ship);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->getEventRepo()->logShipRename($ship, $oldName);
    }

    public function createNewShip(
        string $shipName,
        ShipClass $starterShipClass,
        User $owner
    ): Ship {
        $ship = new Ship(
            $shipName,
            $starterShipClass,
            $owner
        );
        $this->getEntityManager()->persist($ship);

        $this->getEntityManager()->getEventRepo()->logNewShip(
            $ship,
            $owner
        );

        $this->getEntityManager()->flush();

        return $ship;
    }

    public function countClassesForUserId(UuidInterface $userId): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('IDENTITY(tbl.shipClass) as scID', 'COUNT(1) as cnt')
            ->where('IDENTITY(tbl.owner) = :ownerId')
            ->andWhere('tbl.strength > 0')
            ->groupBy('tbl.shipClass')
            ->setParameter('ownerId', $userId->getBytes());
        $results = $qb->getQuery()->getArrayResult();
        $mappedResults = [];
        foreach ($results as $result) {
            $uuid = Uuid::fromBytes($result['scID']);
            $mappedResults[(string)$uuid] = (int)$result['cnt'];
        }
        return $mappedResults;
    }
}
