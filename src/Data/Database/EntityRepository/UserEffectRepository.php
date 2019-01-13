<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UserEffectRepository extends AbstractEntityRepository
{
    private $userCache = [];

    public function countForUserId(UuidInterface $effectId, UuidInterface $userId): int
    {
        $all = $this->getAllForUserId($userId);
        return \count(\array_filter($all, function(array $effect) use ($effectId) {
            $id = $effect['id']; /** @var $id UuidInterface */
            return $id->equals($effectId);
        }));
    }

    private function getAllForUserId(UuidInterface $uuid)
    {
        if (isset($this->userCache[$uuid->toString()])) {
            return $this->userCache[$uuid->toString()];
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->andWhere('IDENTITY(tbl.user) = :userId')
            ->setParameter('userId', $uuid->getBytes());

        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $this->userCache[$uuid->toString()] = $data;

        return $data;
    }
}
