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

    public function updateScore(User $user, int $rateDelta = null): void
    {
        $currentScore = $user->score;
        $rate = $user->scoreRate;
        $previousTime = $user->scoreCalculationTime ?? $this->currentTime;

        $secondsDifference = $this->currentTime->getTimestamp() - $previousTime->getTimestamp();

        $newScore = max(0, $currentScore + ($secondsDifference * $rate));

        if (!is_null($rateDelta)) {
            $rate = $rate + $rateDelta;
        }

        $user->score = $newScore;
        $user->scoreRate = $rate;
        $user->scoreCalculationTime = $this->currentTime;

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
