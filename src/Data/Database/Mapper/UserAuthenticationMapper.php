<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\User;
use App\Domain\Entity\UserAuthentication;

class UserAuthenticationMapper extends Mapper
{
    public function getUserAuthentication(array $item): UserAuthentication
    {
        $domainEntity = new UserAuthentication(
            $item['id'],
            $item['originalCreationTime'],
            $item['lastUsed'],
            $item['expiry'],
            $this->getUser($item),
        );
        return $domainEntity;
    }

    private function getUser(?array $item): ?User
    {
        if (isset($item['user'])) {
            return $this->mapperFactory->createUserMapper()->getUser($item['user']);
        }
        return null;
    }
}
