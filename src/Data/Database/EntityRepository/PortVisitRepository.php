<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\PortVisit;
use App\Data\Database\Entity\User;
use Ramsey\Uuid\UuidInterface;

class PortVisitRepository extends AbstractEntityRepository
{
    public function existsForPortAndUser(
        UuidInterface $portId,
        UuidInterface $playerId
    ): bool {
        return (bool)(int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->where('IDENTITY(tbl.port) = :portId')
            ->andWhere('IDENTITY(tbl.player) = :playerId')
            ->setParameter('portId', $portId->getBytes())
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForPlayerId(UuidInterface $playerId)
    {
        return (int)$this->createQueryBuilder('tbl')
            ->select('count(1)')
            ->andWhere('IDENTITY(tbl.player) = :playerId')
            ->setParameter('playerId', $playerId->getBytes())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function recordVisit(
        User $owner,
        Port $port
    ): void {
        $portVisit = new PortVisit(
            $owner,
            $port,
            $this->dateTimeFactory->now()
        );
        $this->getEntityManager()->persist($portVisit);
        $this->getEntityManager()->flush();
    }
}
