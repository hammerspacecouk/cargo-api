<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\User as DbUser;
use App\Data\ID;
use App\Domain\Entity\User as UserEntity;
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
            ->setParameter('id', $uuid->getBytes())
        ;

        $results = $qb->getQuery()->getArrayResult();
        if (empty($results)) {
            return null;
        }

        $mapper = $this->mapperFactory->createUserMapper();
        return $mapper->getUser(reset($results));
    }

    public function getOrCreateUserByEmail(string $email): UserEntity
    {
        $user = $this->getByEmailAddress($email);
        if ($user) {
            return $user;
        }

        // make a new user
        $user = new DbUser(
            ID::makeNewID(DbUser::class),
            $email,
            Bearing::getInitialRandomStepNumber()
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $user = $this->getByEmailAddress($email);
        if ($user) {
            return $user;
        }

        // something has gone horribly wrong
        throw new \RuntimeException('Could not find the newly made user. It gone and died');
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
        return $mapper->getUser(reset($results));
    }
}
