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
    public function getUser(array $item): User
    {
        $domainEntity = new User(
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
        return $domainEntity;
    }

    private function isAnonymous($item): bool
    {
        return !(
            $item['googleId'] ||
            $item['microsoftId'] ||
            $item['redditId']
        );
    }

    private function mapHomePort($item): ?Port
    {
        if (isset($item['homePort'])) {
            return $this->mapperFactory->createPortMapper()->getPort($item['homePort']);
        }
        return null;
    }

    private function mapRank($item): ?PlayerRank
    {
        if (isset($item['lastRankSeen'])) {
            return $this->mapperFactory->createPlayerRankMapper()->getPlayerRank($item['lastRankSeen']);
        }
        return null;
    }

    private function mapScore($item): Score
    {
        return new Score(
            (int)$item['score'],
            (int)$item['scoreRate'],
            $item['scoreCalculationTime'],
        );
    }
}
