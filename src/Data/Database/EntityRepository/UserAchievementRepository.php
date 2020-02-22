<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Achievement;
use App\Data\Database\Entity\User;
use App\Data\Database\Entity\UserAchievement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserAchievementRepository extends AbstractEntityRepository
{
    public function findForUserId(UuidInterface $userId): array
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'achievement')
            ->join('tbl.achievement', 'achievement')
            ->where('IDENTITY(tbl.user) = :userId')
            ->setParameter('userId', $userId->getBytes());
        return $qb->getQuery()->getArrayResult();
    }

    public function recordCratePickup(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('820a098f-e801-468e-bdb0-105fbb2debff')
        );
    }

    public function recordTravel(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('f38d1e18-3113-4eeb-bc78-7b7154d4a5e4')
        );
    }

    public function recordRenameShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('9a384c28-46cf-4a7f-910e-f61e9ca12ed3')
        );
    }

    private function record(UuidInterface $userId, UuidInterface $achievementId): void
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('IDENTITY(tbl.user) = :userId')
            ->andWhere('IDENTITY(tbl.achievement) = :achievementId')
            ->setParameter('userId', $userId->getBytes())
            ->setParameter('achievementId', $achievementId->getBytes());
        if ($qb->getQuery()->getOneOrNullResult()) {
            // already achieved it
            return;
        }
        $user = $this->_em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId->getBytes())
            ->getQuery()->getSingleResult();
        $achievement = $this->_em->createQueryBuilder()
            ->select('a')
            ->from(Achievement::class, 'a')
            ->where('a.id = :achievementId')
            ->setParameter('achievementId', $achievementId->getBytes())
            ->getQuery()->getSingleResult();

        $userAchievement = new UserAchievement(
            $user,
            $achievement,
            $this->dateTimeFactory->now(),
        );

        $this->getEntityManager()->persist($userAchievement);
        $this->getEntityManager()->flush();
    }
}
