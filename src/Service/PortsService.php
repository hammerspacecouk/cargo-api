<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\Mapper\PortMapper;
use App\Domain\Entity\Port;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PortsService extends AbstractService
{
    private $portMapper;

    public function getByID(
        UuidInterface $uuid
    ): ?Port {

        return $this->mapSingle(
            $this->entityManager->getPortRepo()->getByID($uuid)
        );
    }

    public function countAll()
    {
        $qb = $this->getQueryBuilder(DbPort::class)
            ->where(self::TBL . '.isOpen = true')
            ->select('count(1)');
        ;

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function findAll(
        int $limit,
        int $page = 1
    ): array {
        $qb = $this->getQueryBuilder(DbPort::class)
            ->where(self::TBL . '.isOpen = true')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($limit, $page));

        return $this->mapMany($qb->getQuery()->getArrayResult());
    }

    public function findHomePortForUserId(
        UuidInterface $userId
    ): ?Port {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('tbl', 'p')
            ->innerJoin('tbl.homePort', 'p')
            ->where('tbl.id = :id')
            ->setParameter('id', $userId->getBytes());

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);

        return $this->mapSingle($result['homePort']);
    }

    private function getMapper(): PortMapper
    {
        if (!$this->portMapper) {
            $this->portMapper = $this->mapperFactory->createPortMapper();
        }
        return $this->portMapper;
    }

    private function mapSingle(?array $result): ?Port
    {
        if (!$result) {
            return null;
        }
        return $this->getMapper()->getPort($result);
    }

    /**
     * @return Port[]
     */
    private function mapMany(array $results): array
    {
        return array_map(['self', 'mapSingle'], $results);
    }
}
