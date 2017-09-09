<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\User as DbUser;
use App\Data\ID;
use App\Domain\Entity\User as UserEntity;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UsersService extends AbstractService
{
    public function getById(
        UuidInterface $uuid
    ): ?UserEntity {
    
        $qb = $this->getQueryBuilder(DbUser::class)
            ->where('tbl.id = :id')
            ->setParameter('id', $uuid->getBytes());

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createUserMapper();
        return $mapper->getUser(reset($results));
    }

    public function getByEmailAddress(string $email): ?UserEntity
    {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->where('tbl.email = :email')
            ->setParameter('email', $email);

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createUserMapper();
        return $mapper->getUser(reset($results));
    }

    public function startPlayer(User $user)
    {
        // todo - find a safe haven port (which is open)
        // todo - set the port as the users home port
        // $userEntity->homePort = bob

        // todo - make a new ship
        // todo - put the ship into the homePort
        // todo - add the homeport to the visited ports
        // todo - activate two crates. put one on the ship and one in the port
    }
}
