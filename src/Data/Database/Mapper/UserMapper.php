<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Null\NullUser;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Port;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Market;
use App\Domain\ValueObject\Score;

class UserMapper extends Mapper
{
    /**
     * @param array<mixed> $item
     * @return User
     */
    public function getUser(?array $item): User
    {
        if ($item === null) {
            return new NullUser();
        }

        return new User(
            $item['id'],
            $item['nickname'],
            $item['rotationSteps'],
            $this->mapScore($item),
            $item['emblemSvg'],
            $this->isAnonymous($item),
            $item['gameStartDateTime'],
            $item['permissionLevel'],
            $item['centiDistanceTravelled'],
            new Market(
                $item['marketHistory'],
                $item['marketDiscovery'],
                $item['marketEconomy'],
                $item['marketMilitary'],
            ),
            $this->mapHomePort($item),
            $this->mapRank($item),
        );
    }

    /**
     * @param array<mixed> $item
     * @return bool
     */
    private function isAnonymous(array $item): bool
    {
        return !(
            $item['googleId'] ||
            $item['microsoftId'] ||
            $item['redditId']
        );
    }

    /**
     * @param array<mixed> $item
     * @return Port|null
     */
    private function mapHomePort(array $item): ?Port
    {
        if (isset($item['homePort'])) {
            return $this->mapperFactory->createPortMapper()->getPort($item['homePort']);
        }
        return null;
    }

    /**
     * @param array<mixed> $item
     * @return PlayerRank|null
     */
    private function mapRank(array $item): ?PlayerRank
    {
        if (isset($item['lastRankSeen'])) {
            return $this->mapperFactory->createPlayerRankMapper()->getPlayerRank($item['lastRankSeen']);
        }
        return null;
    }

    /**
     * @param array<mixed> $item
     * @return Score
     */
    private function mapScore(array $item): Score
    {
        return new Score(
            (int)$item['score'],
            (int)$item['scoreRate'],
            $item['scoreCalculationTime'],
        );
    }
}
