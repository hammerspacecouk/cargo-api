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
    /**
     * @var array<string, mixed>
     */
    private $userCache = [];

    /**
     * @param UuidInterface $uuid
     * @param int $resultType
     * @return mixed
     */
    public function getByIDWithEffect(
        UuidInterface $uuid,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'effect')
            ->where('tbl.id = :id')
            ->join('tbl.effect', 'effect')
            ->setParameter('id', $uuid->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    /**
     * @param UuidInterface $userId
     * @return mixed
     */
    public function getAllForUserId(UuidInterface $userId)
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

    public function deleteForUserId(UuidInterface $userId): void
    {
        $this->createQueryBuilder('tbl')
            ->delete(UserEffect::class, 'tbl')
            ->where('IDENTITY(tbl.user) = :userId')
            ->setParameter('userId', $userId->getBytes())
            ->getQuery()
            ->execute();
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
