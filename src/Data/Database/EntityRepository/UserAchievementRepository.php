<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Achievement;
use App\Data\Database\Entity\User;
use App\Data\Database\Entity\UserAchievement;
use App\Infrastructure\DateTimeFactory;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserAchievementRepository extends AbstractEntityRepository
{
    private const SPECIAL_VISITS = [
        // portId -> achievementId
        'd21215ca-03fe-4ff1-9f7a-64ff50141b31' => '55091f43-3c30-47a8-a770-46299b3cd168',
        '00000000-0000-4000-8000-000000000000' => 'd3bb9496-2a24-425a-89bb-e066bf9da31b',
    ];

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

    public function recordPurchase(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('9447676b-9437-4c43-8ba4-506a5039293d')
        );
    }

    public function recordFirstTravel(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('f38d1e18-3113-4eeb-bc78-7b7154d4a5e4')
        );
    }

    public function recordDistance(UuidInterface $userId, int $beforeValue, int $afterValue): void
    {
        $t1 = 100 * 10;
        $t2 = 100 * 100;
        $t3 = 100 * 1000;
        $t4 = 100 * 10000;

        $a = null;
        if ($beforeValue < $t1 && $afterValue >= $t1) {
            $a = 'c634acb9-1924-45fc-9d3c-8e78ee609218';
        }
        if ($beforeValue < $t2 && $afterValue >= $t2) {
            $a = 'cfcefa80-f6a9-4a89-8977-11db52bd6f16';
        }
        if ($beforeValue < $t3 && $afterValue >= $t3) {
            $a = 'f7f3b544-f854-4005-8a4d-a0570dd53e67';
        }
        if ($beforeValue < $t4 && $afterValue >= $t4) {
            $a = '36f2633c-4474-4015-ba86-6822db4b0dc5';
        }

        if ($a) {
            $this->record($userId, Uuid::fromString($a));
        }
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

    public function recordAttackHospitalShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('7bf3208d-2ab2-418e-bb8c-c8cd61fa5e67')
        );
    }

    public function recordDestroyedShip(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('6c81e527-7be2-4b3a-86f8-a13de9507d67')
        );
    }

    public function recordDefenceEffect(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('a758f828-0d16-4d9e-b234-b75369883ae0')
        );
    }

    public function recordTravelEffect(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('d5e330da-e0d8-4af4-a161-6962bce43287')
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

    public function recordBlockade(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('0e6e4a49-18c8-4e81-bef4-cb0bb386562d')
        );
    }

    public function recordBreakBlockade(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('92a86003-772d-4616-8bda-e1da1735926a')
        );
    }

    public function recordMakeConvoy(UuidInterface $userId): void
    {
        $this->record(
            $userId,
            Uuid::fromString('3e555a89-0c50-40ee-a608-9944f8af2506')
        );
    }

    public function recordSpecialVisit(UuidInterface $userId, UuidInterface $portId): void
    {
        $id = self::SPECIAL_VISITS[$portId->toString()] ?? null;
        if ($id) {
            $this->record(
                $userId,
                Uuid::fromString($id)
            );
        }
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
            DateTimeFactory::now(),
        );

        $this->getEntityManager()->persist($userAchievement);
        $this->getEntityManager()->flush();
    }
}
