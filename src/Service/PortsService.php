<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Port;
use App\Domain\Entity\Port as PortEntity;
use Ramsey\Uuid\UuidInterface;

class PortsService extends AbstractService
{
    public function makeNew():void
    {
        $crate = new Port((string) time());

        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

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
