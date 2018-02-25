<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PortVisitRepository extends AbstractEntityRepository
{
    public function existsForPortAndUser(
        UuidInterface $portId,
        UuidInterface $playerId
    ): bool {
        return !!(int)$this->createQueryBuilder('tbl')
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
}
