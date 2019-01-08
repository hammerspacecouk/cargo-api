<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\PortVisit;
use App\Data\Database\Entity\User;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PortVisitRepository extends AbstractEntityRepository
{
    public function getForPortAndUser(
        UuidInterface $portId,
        UuidInterface $playerId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        return $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.port) = :portId')
            ->andWhere('IDENTITY(tbl.player) = :playerId')
            ->setParameter('portId', $portId->getBytes())
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->getOneOrNullResult($resultType);
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

    public function recordVisit(
        ?PortVisit $visit,
        User $owner,
        Port $port
    ): void {
        if (!$visit) {
            $visit = new PortVisit(
                $owner,
                $port,
                $this->dateTimeFactory->now(),
            );
        } else {
            $visit->lastVisited = $this->dateTimeFactory->now();
        }
        $this->getEntityManager()->persist($visit);
        $this->getEntityManager()->flush();
    }
}
