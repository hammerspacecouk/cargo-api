<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\User;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UserRepository extends AbstractEntityRepository
{
    public function newPlayer(
        ?string $queryHash,
        ?string $ipHash,
        int $rotationSteps,
        Port $homePort
    ): User {
        $user = new User(
            $queryHash,
            $ipHash,
            $rotationSteps,
            $homePort
        );
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->getEventRepo()->logNewPlayer($user, $homePort);
        return $user;
    }

    public function getByQueryHash(
        string $queryHash,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.queryHash = :hash')
            ->setParameter('hash', $queryHash);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function getByIpHash(
        string $queryHash,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.anonymousIpHash = :hash')
            ->setParameter('hash', $queryHash);
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function updateScoreRate(User $user, int $rateDelta = 0): void
    {
        $rate = ($user->scoreRate + $rateDelta);
        $newScore = $this->currentScore($user);

        $user->score = $newScore;
        $user->scoreRate = $this->clampRate($rate);
        $user->scoreCalculationTime = $this->currentTime;

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function updateScoreValue(User $user, int $scoreDelta = 0): void
    {
        $newScore = ($this->currentScore($user) + $scoreDelta);

        $user->score = $this->clampScore($newScore);
        $user->scoreCalculationTime = $this->currentTime;

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function currentScore(User $user): int
    {
        $currentScore = $user->score;
        $rate = $user->scoreRate;
        $previousTime = ($user->scoreCalculationTime ?? $this->currentTime);

        $secondsDifference = ($this->currentTime->getTimestamp() - $previousTime->getTimestamp());
        $delta = ($secondsDifference * $rate);

        return $this->clampScore($currentScore + $delta);
    }

    private function clampScore($score): int
    {
        // ensure that scores are always above zero and capped at the max int value
        return (int)max(0, min($score, PHP_INT_MAX));
    }

    private function clampRate($rate): int
    {
        $maxDelta = (2 ** 30);
        return (int)max(-$maxDelta, min($rate, $maxDelta));
    }

    public function clearHashesBefore(\DateTimeImmutable $before): void
    {
        $qb = $this->createQueryBuilder('tbl')
            ->update()
            ->set('tbl.anonymousIpHash', 'NULL')
            ->where('tbl.createdAt < :before')
            ->setParameter('before', $before);
        $qb->getQuery()->execute();
    }

    public function findWithLastSeenRank(
        UuidInterface $userId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'r')
            ->join('tbl.lastRankSeen', 'r')
            ->where('tbl.id = :userId')
            ->setParameter('userId', $userId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }
}
