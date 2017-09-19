<?php declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\User;
use App\Data\ID;
use App\Domain\ValueObject\Bearing;

class UserRepository extends AbstractEntityRepository
{
    public function getByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function createByEmail(string $email): User
    {
        $user = new User(
            ID::makeNewID(User::class),
            $email,
            Bearing::getInitialRandomStepNumber()
        );

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }
}
