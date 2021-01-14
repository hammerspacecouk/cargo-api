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
    private ?PortMapper $portMapper = null;

    public function getByID(
        UuidInterface $uuid
    ): ?Port {
        return $this->mapSingle(
            $this->entityManager->getPortRepo()->getByID($uuid)
        );
    }

    public function countAll(): int
    {
        $qb = $this->getQueryBuilder(DbPort::class)
            ->where(self::TBL . '.isOpen = true')
            ->select('count(1)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $limit
     * @param int $page
     * @return Port[]
     */
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
    ): Port {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('tbl', 'p')
            ->innerJoin('tbl.homePort', 'p')
            ->where('tbl.id = :id')
            ->setParameter('id', $userId->getBytes());

        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        $mapped = $this->mapSingle($result['homePort']);
        if (!$mapped) {
            throw new \LogicException('All users have a home port');
        }
        return $mapped;
    }

    public function findAllVisitedPortsForUserId(
        UuidInterface $userId
    ): array {
        $results = $this->entityManager->getPortVisitRepo()->getAllForPlayerId($userId);
        return $this->mapMany(array_column($results, 'port'));
    }

    private function getMapper(): PortMapper
    {
        if (!$this->portMapper) {
            $this->portMapper = $this->mapperFactory->createPortMapper();
        }
        return $this->portMapper;
    }

    /**
     * @param array[]|null $result
     * @return Port|null
     */
    private function mapSingle(?array $result): ?Port
    {
        if (!$result) {
            return null;
        }
        return $this->getMapper()->getPort($result);
    }

    /**
     * @param array[] $results
     * @return Port[]
     */
    private function mapMany(array $results): array
    {
        return array_map(function ($result) {
            return $this->mapSingle($result);
        }, $results);
    }
}
