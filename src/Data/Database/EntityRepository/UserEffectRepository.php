<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Effect;
use App\Data\Database\Entity\User;
use App\Data\Database\Entity\UserEffect;
use function App\Functions\Arrays\groupByValue;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UserEffectRepository extends AbstractEntityRepository
{
    // purposely only cached for this request
    private $userCache = [];

    public function getByIDWithEffect(
        UuidInterface $uuid,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'effect')
            ->where('tbl.id = :id')
            ->join('tbl.effect', 'effect')
            ->setParameter('id', $uuid->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function countForUserId(UuidInterface $effectId, UuidInterface $userId): int
    {
        $all = $this->getAllForUserId($userId);
        return \count(\array_filter($all, function (array $userEffect) use ($effectId) {
            $id = $userEffect['effect']['id'];
            /** @var $id UuidInterface */
            return $id->equals($effectId);
        }));
    }

    public function getUniqueOfTypeForUserId(UuidInterface $userId, string $type): array
    {
        $allOfTypeForUser = \array_filter($this->getAllForUserId($userId), function ($result) use ($type) {
            return $result['effect']['type'] === $type;
        });
        return groupByValue($allOfTypeForUser, function ($result) {
            return (string)$result['effect']['id'];
        });
    }

    private function getAllForUserId(UuidInterface $userId)
    {
        if (isset($this->userCache[$userId->toString()])) {
            return $this->userCache[$userId->toString()];
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'effect')
            ->join('tbl.effect', 'effect')
            ->where('tbl.usedAt IS NULL')
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

        $this->getEntityManager()->flush();

        return $effect;
    }

    public function useEffect(
        UserEffect $effect
    ): void {
        $effect->usedAt = $this->dateTimeFactory->now();

        $this->getEntityManager()->persist($effect);
        $this->getEntityManager()->flush();
    }
}
