<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\User;
use App\Domain\ValueObject\Colour;
use App\Service\Oauth\OAuthServiceInterface;
use App\Service\UsersService;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Numbers\clamp;

class UserRepository extends AbstractEntityRepository implements CleanableInterface
{
    private const MAX_RATE_DELTA = 2 ** 30;

    public function newPlayer(
        ?string $ipHash,
        Colour $colour,
        int $rotationSteps,
        Port $homePort,
        PlayerRank $initialRank,
        ?string $oauthHash,
        UsersService $service
    ): User {
        $user = new User(
            $ipHash,
            $colour->getHex(),
            $rotationSteps,
            $homePort,
            $initialRank,
        );
        if ($oauthHash && $service instanceof OAuthServiceInterface) {
            $user = $service->attachHash($user, $oauthHash);
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->getEventRepo()->logNewPlayer($user, $homePort);
        return $user;
    }

    public function getByIDWithHomePort(
        UuidInterface $userId,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl', 'homePort')
            ->join('tbl.homePort', 'homePort')
            ->where('tbl.id = :id')
            ->setParameter('id', $userId->getBytes());
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function countByIpHash(string $ipHash): int
    {
        $qb = $this->createQueryBuilder('tbl')
            ->select('COUNT(1)')
            ->where('tbl.anonymousIpHash = :hash')
            ->setParameter('hash', $ipHash);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    public function updateScoreRate(User $user, int $rateDelta = 0): void
    {
        $rate = ($user->scoreRate + $rateDelta);
        $newScore = $this->currentScore($user);

        $user->score = $newScore;
        $user->scoreRate = $this->clampRate($rate);
        $user->scoreCalculationTime = $this->dateTimeFactory->now();

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function updateScoreValue(User $user, int $scoreDelta = 0): void
    {
        $now = $this->dateTimeFactory->now();
        $newScore = ($this->currentScore($user) + $scoreDelta);

        $user->score = $this->clampScore($newScore);
        $user->scoreCalculationTime = $now;

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function currentScore(User $user): int
    {
        $now = $this->dateTimeFactory->now();
        $currentScore = $user->score;
        $rate = $user->scoreRate;
        $previousTime = ($user->scoreCalculationTime ?? $now);

        $secondsDifference = ($now->getTimestamp() - $previousTime->getTimestamp());
        $delta = ($secondsDifference * $rate);

        return $this->clampScore($currentScore + $delta);
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

    public function clean(\DateTimeImmutable $now): int
    {
        // remove IP hashes from User accounts older than X minutes
        $count = $this->clearHashesBefore(
            $now->sub($this->applicationConfig->getIpLifetimeInterval())
        );

        // todo - remove users who never made a move:
        // todo - anonymous users that haven't made a move after 24 hours (delete log lines)
        // todo - (port_visits === 1 after 1 week for anonymous)
        // todo - (port_visits === 1 after 1 month for registered)

        return $count;
    }

    private function clampScore($score): int
    {
        return clamp($score, 0, PHP_INT_MAX);
    }

    private function clampRate($rate): int
    {
        return clamp($rate, -self::MAX_RATE_DELTA, self::MAX_RATE_DELTA);
    }

    private function clearHashesBefore(\DateTimeImmutable $before): int
    {
        $qb = $this->createQueryBuilder('tbl')
            ->update()
            ->set('tbl.anonymousIpHash', 'NULL')
            ->set('tbl.updatedAt', ':now')
            ->where('tbl.anonymousIpHash IS NOT NULL')
            ->andWhere('tbl.createdAt < :before')
            ->setParameter('before', $before)
            ->setParameter('now', $this->dateTimeFactory->now());
        return $qb->getQuery()->execute();
    }
}
