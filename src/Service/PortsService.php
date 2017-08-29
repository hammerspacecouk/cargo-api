<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\Port;
use App\Data\ID;
use App\Domain\Entity\Port as PortEntity;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PortsService extends AbstractService
{
//    public function makeChannelBetween(
//        UuidInterface $fromId,
//        UuidInterface $toId,
//        Bearing $bearing,
//        int $distance
//    ): void {
//        $portRepo = $this->entityManager->getPortRepo();
//
//        $fromPort = $portRepo->getByID($fromId, Query::HYDRATE_OBJECT);
//        $toPort = $portRepo->getByID($toId, Query::HYDRATE_OBJECT);
//
//        if (!$fromPort || !$toPort) {
//            throw new \InvalidArgumentException('Could not find both ports');
//        }
//
//        $channel = new Channel(
//            ID::makeNewID(Channel::class),
//            $fromPort,
//            $toPort,
//            (string) $bearing,
//            $distance
//        );
//
//        $this->entityManager->persist($channel);
//        $this->entityManager->flush();
//    }

    public function countAll()
    {
        $qb = $this->getQueryBuilder(Port::class)
            ->select('count(1)');
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findAll(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(Port::class)
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page))
        ;

        $mapper = $this->mapperFactory->createPortMapper();

        $results = $qb->getQuery()->getArrayResult();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getPort($result);
        }, $results);
    }

    public function getByID(
        UuidInterface $uuid
    ): ?PortEntity {
        $qb = $this->getQueryBuilder(Port::class)
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes())
        ;

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createPortMapper();
        return $mapper->getPort($results[0]);
    }
}
