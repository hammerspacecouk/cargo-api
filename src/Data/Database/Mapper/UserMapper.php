<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Port;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Score;
use DateTimeImmutable;

class UserMapper extends Mapper
{
    public function getUser(array $item): User
    {
        $domainEntity = new User(
            $item['id'],
            $item['rotationSteps'],
            $this->mapScore($item),
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
        $date = new DateTimeImmutable('1970-01-01T00:00:00Z');
        if ($item['scoreCalculationTime']) {
            $date = DateTimeImmutable::createFromMutable($item['scoreCalculationTime']);
        }

        return new Score(
            (int)$item['score'],
            (int)$item['scoreRate'],
            $date
        );
    }
}
