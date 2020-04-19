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

    public function deleteForUserId(UuidInterface $userId): void
    {
        $this->createQueryBuilder('tbl')
            ->delete(UserAchievement::class, 'tbl')
            ->where('IDENTITY(tbl.user) = :userId')
            ->setParameter('userId', $userId->getBytes())
            ->getQuery()
            ->execute();
    }

    public function recordCratePickup(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('820a098f-e801-468e-bdb0-105fbb2debff')
        );
    }

    public function recordGoalCratePickup(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('5031ad8e-763c-4f3a-b537-fd16ea667b22')
        );
    }

    public function recordHoarding(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('f02aff86-47f0-4848-9b63-674a4247d7dd')
        );
    }

    public function recordFirstTravel(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('f38d1e18-3113-4eeb-bc78-7b7154d4a5e4')
        );
    }

    public function recordLongTravel(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('f8cd00cb-315d-42a5-8270-7793ebe57327')
        );
    }

    public function recordLaunchedShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('f155544a-c733-46b0-8e2a-acf3e84638e3')
        );
    }

    public function recordRenameShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('9a384c28-46cf-4a7f-910e-f61e9ca12ed3')
        );
    }

    public function recordShipDestroyed(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('690f6b47-fdde-4acb-be4d-a9bc2ac0a33e')
        );
    }

    public function recordShipAttacked(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('5ac1ce5c-d029-4863-bfef-ee52a7efc2a4')
        );
    }

    public function recordAttackedShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('dc376f2e-d255-4b4e-b3a5-101735711c8d')
        );
    }

    public function recordDestroyedShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('6c81e527-7be2-4b3a-86f8-a13de9507d67')
        );
    }

    public function recordArrivalToUnsafeTerritory(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('89bfabe1-5ae3-4971-94b7-df4279949c64')
        );
    }

    public function recordContactWithInfected(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('6b08dc29-a47a-4483-a500-913acfa6119e')
        );
    }

    public function recordCured(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('223f51e1-7813-4829-9bcd-78396ec43d8d')
        );
    }

    public function recordRepairedShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('8dd0a7f3-955a-4b14-8b50-3bc744d2bba2')
        );
    }

    public function recordWin(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('134b0330-577d-476e-92c7-602f957284d3')
        );
    }

    public function recordAddedToLeaderBoard(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('5d75b968-6080-46e6-a269-1b09adbf1216')
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
