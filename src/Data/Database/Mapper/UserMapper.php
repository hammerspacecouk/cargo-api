<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

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
            $item['queryHash'] !== null,
            $item['createdAt'],
            $item['updatedAt'],
            $this->mapHomePort($item)
        );
        return $domainEntity;
    }

    private function mapHomePort($item): ?Port
    {
        if (isset($item['homePort'])) {
            return $this->mapperFactory->createPortMapper()->getPort($item['homePort']);
        }
        return null;
    }

    private function mapScore($item): Score
    {
        return new Score(
            (int)$item['score'],
            (int)$item['scoreRate'],
            $item['scoreCalculationTime']
        );
    }
}
