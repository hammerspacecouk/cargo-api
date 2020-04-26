<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipClass;
use App\Data\Database\Entity\User;
use App\Data\Database\Filters\DeletedItemsFilter;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipRepository extends AbstractEntityRepository implements CleanableInterface
{
    /**
     * @param UuidInterface $shipId
     * @param UuidInterface $ownerId
     * @param int $resultType
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getShipForOwnerId(
        UuidInterface $shipId,
        UuidInterface $ownerId,
        int $resultType = Query::HYDRATE_ARRAY
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

    /**
     * @param UuidInterface $uuid
     * @param int $resultType
     * @return mixed
     */
    public function getByConvoyID(
        UuidInterface $uuid,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'c', 'o')
            ->join('tbl.shipClass', 'c')
            ->join('tbl.owner', 'o')
            ->where('tbl.convoyUuid = :id')
            ->setParameter('id', $uuid);
        return $qb->getQuery()->getResult($resultType);
    }

    public function deleteByOwnerId(UuidInterface $userId): void
    {
        $this->createQueryBuilder('tbl')
            ->delete(Ship::class, 'tbl')
            ->where('IDENTITY(tbl.owner) = :ownerId')
            ->setParameter('ownerId', $userId->getBytes())
        ->getQuery()
        ->execute();
    }

    public function renameShip(
        UuidInterface $shipId,
        string $newName
    ): void {
        /** @var Ship|null $ship */
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
        User $owner,
        int $purchasePrice = 0
    ): Ship {
        $ship = new Ship(
            $shipName,
            $starterShipClass,
            $owner,
            $purchasePrice
        );
        $this->getEntityManager()->persist($ship);

        $this->getEntityManager()->getEventRepo()->logNewShip(
            $ship,
            $owner,
        );

        $this->getEntityManager()->flush();

        return $ship;
    }


    public function getEntireFleetForOwner(UuidInterface $userId): array
    {
        // Also include deleted items
        $this->_em->getFilters()->disable(DeletedItemsFilter::FILTER_NAME);

        $qb = $this->createQueryBuilder('tbl')
            ->where('IDENTITY(tbl.owner) = :ownerId')
            ->setParameter('ownerId', $userId->getBytes());
        $result = $qb->getQuery()->getArrayResult();

        $this->_em->getFilters()->enable(DeletedItemsFilter::FILTER_NAME);
        return $result;
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
            $mappedResults[$uuid->toString()] = (int)$result['cnt'];
        }
        return $mappedResults;
    }

    public function userHasStarterShip(UuidInterface $userId): bool
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('COUNT(1)')
            ->join('tbl.shipClass', 'shipClass')
            ->where('IDENTITY(tbl.owner) = :ownerId')
            ->andWhere('tbl.strength > 0')
            ->andWhere('shipClass.isStarterShip = true')
            ->setParameter('ownerId', $userId->getBytes());
        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function updateStrengthValue(Ship $ship, int $strengthDelta = 0): int
    {
        $newStrength = ($ship->strength + $strengthDelta);
        if ($newStrength < 0) {
            $newStrength = 0;
        }

        $ship->strength = $newStrength;

        $this->getEntityManager()->persist($ship);
        $this->getEntityManager()->flush();

        return (int)$newStrength;
    }

    public function removeDestroyed(DateTimeImmutable $before): int
    {
        $entity = Ship::class;
        $sql = "DELETE FROM $entity t WHERE t.strength = 0 AND t.updatedAt < :beforeTime";
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('beforeTime', $before);
        return $query->execute();
    }

    public function clean(DateTimeImmutable $now): int
    {
        return $this->removeDestroyed($now->sub(new DateInterval('P14D')));
    }

    public function infectRandomShip(): ?Ship
    {
        $countQuery = $this->createQueryBuilder('tbl')
            ->select('COUNT(1)')
            ->join('tbl.shipClass', 'shipClass')
            ->where('tbl.hasPlague = true')
            ->andWhere('shipClass.isHospitalShip = false')
            ->andWhere('shipClass.autoNavigate = false');
        if ((int)$countQuery->getQuery()->getSingleScalarResult() > 5) {
            // enough are already infected
            return null;
        }

        $qb = $this->createQueryBuilder('tbl')
            ->join('tbl.shipClass', 'shipClass')
            ->where('tbl.hasPlague = false')
            ->andWhere('shipClass.isHospitalShip = false')
            ->andWhere('shipClass.autoNavigate = false');
        $cQb = clone $qb;
        $count = (int)$cQb->select('COUNT(1)')
            ->getQuery()->getSingleScalarResult();
        if ($count === 0) {
            return null;
        }

        $randomOffset = random_int(0, $count - 1);

        $qb->select('tbl', 'shipClass')
            ->setFirstResult($randomOffset)
            ->setMaxResults(1);
        $ship = $qb->getQuery()->getOneOrNullResult();
        if ($ship) {
            $ship->hasPlague = true;
            $this->getEntityManager()->persist($ship);
            $this->getEntityManager()->flush();
            return $ship;
        }
        return null;
    }
}
