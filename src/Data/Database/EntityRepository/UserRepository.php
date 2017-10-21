<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\User;
use App\Data\ID;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;

class UserRepository extends AbstractEntityRepository
{
    public function getByEmail(
        string $email,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.email = :email')
            ->setParameter('email', $email);
        return $qb->getQuery()->getOneOrNullResult($resultType);
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
