<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\User as DbUser;
use App\Data\ID;
use App\Domain\Entity\User as UserEntity;
use Doctrine\ORM\Query;

class UsersService extends AbstractService
{
    public function getOrCreateUserByEmail(string $email): UserEntity
    {
        $user = $this->getByEmailAddress($email);
        if ($user) {
            return $user;
        }

        // make a new user
        $user = new DbUser(
            ID::makeNewID(DbUser::class),
            $email
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->getByEmailAddress($email);
    }

    public function getByEmailAddress(string $email): ?UserEntity
    {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->where('tbl.email = :email')
            ->setParameter('email', $email)
        ;

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createUserMapper();
        return $mapper->getUser($results[0]);
    }
}
