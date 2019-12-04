<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Port;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Colour;
use App\Domain\ValueObject\Score;

class UserMapper extends Mapper
{
    /**
     * @param array<mixed> $item
     * @return User
     */
    public function getUser(array $item): User
    {
        return new User(
            $item['id'],
            $item['rotationSteps'],
            new Colour($item['colour']),
            $this->mapScore($item),
            $this->isAnonymous($item),
            $item['createdAt'],
            $item['permissionLevel'],
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
