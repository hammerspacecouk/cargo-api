<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\PortVisit;
use App\Data\Database\Entity\User;
use App\Infrastructure\DateTimeFactory;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PortVisitRepository extends AbstractEntityRepository
{
    public function getForPortAndUser(
        UuidInterface $portId,
        UuidInterface $playerId,
        int $resultType = Query::HYDRATE_ARRAY
    ): mixed {
        return $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.port) = :portId')
            ->andWhere('IDENTITY(tbl.player) = :playerId')
            ->setParameter('portId', $portId->getBytes())
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->getOneOrNullResult($resultType);
    }

    public function getAllForPlayerId(UuidInterface $playerId): array
    {
        return $this->createQueryBuilder('tbl')
            ->select('tbl', 'port')
            ->join('tbl.port', 'port')
            ->andWhere('IDENTITY(tbl.player) = :playerId')
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->getArrayResult();
    }

    public function countForPlayerId(UuidInterface $playerId): int
    {
        return (int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->andWhere('IDENTITY(tbl.player) = :playerId')
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteForPlayerId(UuidInterface $playerId): void
    {
        $this->createQueryBuilder('tbl')
            ->delete(PortVisit::class, 'tbl')
            ->where('IDENTITY(tbl.player) = :playerId')
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->execute();
    }

    public function recordVisit(
        ?PortVisit $visit,
        User $owner,
        Port $port
    ): void {
        if (!$visit) {
            $visit = new PortVisit(
                $owner,
                $port,
                DateTimeFactory::now(),
            );
        } else {
            $visit->lastVisited = DateTimeFactory::now();
        }
        $this->getEntityManager()->persist($visit);
        $this->getEntityManager()->flush();
    }
}
