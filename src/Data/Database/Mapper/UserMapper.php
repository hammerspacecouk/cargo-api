<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

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
            $this->mapScore($item)
        );
        return $domainEntity;
    }

    private function mapScore($item): Score
    {
        $date = new DateTimeImmutable('1970-01-01T00:00:00Z');
        if ($item['scoreCalculationTime']) {
            $date = DateTimeImmutable::createFromMutable($item['scoreCalculationTime']);
        }

        return new Score(
            $item['score'],
            $item['scoreRate'],
            $date
        );
    }
}
