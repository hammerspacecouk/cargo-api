<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ChannelRepository extends AbstractEntityRepository
{
    public function getAllLinkedToPortId(
        UuidInterface $id,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'fromPort', 'toPort', 'minimumEntryRank')
            ->join('tbl.fromPort', 'fromPort')
            ->join('tbl.toPort', 'toPort')
            ->leftJoin('tbl.minimumEntryRank', 'minimumEntryRank')
            ->where('IDENTITY(tbl.fromPort) = :id')
            ->orWhere('IDENTITY(tbl.toPort) = :id')
            ->setParameter('id', $id->getBytes());

        return $qb->getQuery()->getResult($resultType);
    }
}
