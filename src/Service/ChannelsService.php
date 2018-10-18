<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Channel as DbChannel;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;

class ChannelsService extends AbstractService
{
    public function getAll(): array
    {
        $qb = $this->getQueryBuilder(DbChannel::class)
            ->select('tbl', 'fromPort', 'toPort')
            ->join('tbl.fromPort', 'fromPort')
            ->join('tbl.toPort', 'toPort');

        $results = $qb->getQuery()->getArrayResult();

        $mapper = $this->mapperFactory->createChannelMapper();

        return array_map(function ($result) use ($mapper) {
            return $mapper->getChannel($result);
        }, $results);
    }

    /** @return Channel[] */
    public function getAllLinkedToPort(Port $port): array
    {
        $qb = $this->getQueryBuilder(DbChannel::class)
            ->select('tbl', 'fromPort', 'toPort', 'minimumEntryRank')
            ->join('tbl.fromPort', 'fromPort')
            ->join('tbl.toPort', 'toPort')
            ->leftJoin('tbl.minimumEntryRank', 'minimumEntryRank')
            ->where('IDENTITY(tbl.fromPort) = :id')
            ->orWhere('IDENTITY(tbl.toPort) = :id')
            ->setParameter('id', $port->getId()->getBytes());

        $results = $qb->getQuery()->getArrayResult();

        $mapper = $this->mapperFactory->createChannelMapper();

        return array_map(function ($result) use ($mapper) {
            return $mapper->getChannel($result);
        }, $results);
    }
}
