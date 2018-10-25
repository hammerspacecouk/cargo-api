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

    /**
     * @param Port $port
     * @return Channel[]
     */
    public function getAllLinkedToPort(Port $port): array
    {
        $mapper = $this->mapperFactory->createChannelMapper();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getChannel($result);
        }, $this->entityManager->getChannelRepo()->getAllLinkedToPortId($port->getId()));
    }
}
