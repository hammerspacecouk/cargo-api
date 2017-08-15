<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\User;
use App\Data\ID;
use App\Domain\ValueObject\Bearing;

class UserRepository extends AbstractEntityRepository
{
    public function getOrCreateUserByEmail(string $email): User
    {
        $user = $this->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User(
                ID::makeNewID(User::class),
                $email,
                Bearing::getInitialRandomStepNumber()
            );

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        return $user;
    }
}
