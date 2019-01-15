<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Effect;
use App\Data\Database\Entity\User;
use App\Data\Database\Entity\UserEffect;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UserEffectRepository extends AbstractEntityRepository
{
    private $userCache = [];

    public function countForUserId(UuidInterface $effectId, UuidInterface $userId): int
    {
        $all = $this->getAllForUserId($userId);
        return \count(\array_filter($all, function (array $userEffect) use ($effectId) {
            $id = $userEffect['effect']['id'];
            /** @var $id UuidInterface */
            return $id->equals($effectId);
        }));
    }

    private function getAllForUserId(UuidInterface $userId)
    {
        if (isset($this->userCache[$userId->toString()])) {
            return $this->userCache[$userId->toString()];
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'effect')
            ->join('tbl.effect', 'effect')
            ->andWhere('IDENTITY(tbl.user) = :userId')
            ->setParameter('userId', $userId->getBytes());

        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $this->userCache[$userId->toString()] = $data;

        return $data;
    }

    public function createNew(
        Effect $effectEntity,
        User $user
    ): UserEffect {
        $effect = new UserEffect(
            $user,
            $effectEntity,
            $this->dateTimeFactory->now(),
        );
        $this->getEntityManager()->persist($effect);

        $this->getEntityManager()->getEventRepo()->logNewPlayerEffect(
            $user,
            $effectEntity,
        );

        $this->getEntityManager()->flush();

        return $effect;
    }
}
