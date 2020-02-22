<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\AuthenticationToken as DbAuthenticationToken;
use App\Data\Database\Entity\User as DbUser;
use App\Domain\ValueObject\AuthProvider;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Arrays\find;

class StatsService extends AbstractService
{
    public function countAllUsers(): int
    {
        return $this->entityManager->getUserRepo()->count([]);
    }

    public function countActiveUsers(): int
    {
        $oneMonthAgo = $this->dateTimeFactory->now()->sub(new \DateInterval('P1M'));

        $qb = $this->getQueryBuilder(DbAuthenticationToken::class)
            ->select('COUNT(DISTINCT(IDENTITY(tbl.user)))')
            ->where('tbl.lastUsed >= :oneMonthAgo')
            ->setParameter('oneMonthAgo', $oneMonthAgo);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array[]
     */
    public function countRanks(): array
    {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('rank.id', 'COUNT(rank) AS theCount')
            ->join('tbl.lastRankSeen', 'rank')
            ->groupBy('rank.id');

        $results = $qb->getQuery()->getArrayResult();

        $allRanks = $this->entityManager->getPlayerRankRepo()->getList();

        return array_map(function ($rank) use ($results) {
            $countResult = find(function ($result) use ($rank) {
                /** @var UuidInterface $id */
                $id = $result['id'];
                return $rank['id']->equals($id);
            }, $results);

            return [
                'name' => $rank['name'],
                'count' => $countResult ? $countResult['theCount'] : 0,
            ];
        }, $allRanks);
    }

    /**
     * @return array<string, int>
     */
    public function countAuthProviders(): array
    {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('COUNT(1)');

        foreach (AuthProvider::ALL_PROVIDERS as $provider) {
            $qb = $qb->andWhere('tbl.' . $provider . 'Id IS NULL');
        }

        $counts = [
            'anonymous' => (int)$qb->getQuery()->getSingleScalarResult(),
        ];

        foreach (AuthProvider::ALL_PROVIDERS as $provider) {
            $counts[$provider] = $this->countUserColumnNotNull($provider . 'Id');
        }

        return $counts;
    }

    /**
     * @param DateTimeImmutable $since
     * @param DateTimeImmutable $until
     * @return array<int|string, mixed>
     */
    public function registrationsPerDay(
        DateTimeImmutable $since,
        DateTimeImmutable $until
    ): array {
        $query = <<<SQL
            SELECT DATE(created_at) AS theDate, COUNT(*) AS theCount
            FROM users
            WHERE created_at >= :since
            AND created_at <= :until
            GROUP BY DATE(created_at)
            ORDER BY theDate ASC
        SQL;

        $mysqlFormat = 'Y-m-d H:i:s';
        $tz = new DateTimeZone('UTC');
        $results = $this->entityManager->getConnection()->executeQuery(
            $query,
            [
                'since' => $since->setTimezone($tz)->format($mysqlFormat),
                'until' => $until->setTimezone($tz)->format($mysqlFormat),
            ]
        )->fetchAll();

        if (empty($results)) {
            return [];
        }
        $table = [];
        foreach ($results as $result) {
            $table[$result['theDate']] = $result['theCount'];
        }

        $dateCounter = $since;
        while ($dateCounter <= $until) {
            $date = $dateCounter->format('Y-m-d');
            if (!isset($table[$date])) {
                $table[$date] = 0;
            }
            $dateCounter = $dateCounter->add(new DateInterval('P1D'));
        }

        ksort($table);
        return $table;
    }

    private function countUserColumnNotNull(string $column): int
    {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('COUNT(1)')
            ->where('tbl.' . $column . ' IS NOT NULL');
        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
