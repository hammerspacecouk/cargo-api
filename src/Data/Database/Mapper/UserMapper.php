<?php declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\User;

class UserMapper extends Mapper
{
    public function getUser(array $item): User
    {
        $domainEntity = new User(
            $item['id'],
            $item['email'],
            $item['rotationSteps']
        );
        return $domainEntity;
    }
}
