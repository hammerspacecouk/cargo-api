<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\User;
use App\Data\ID;
use App\Domain\ValueObject\Bearing;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UserRepository extends AbstractEntityRepository
{
    // e-mail addresses need to be obfuscated in the database,
    // but transparent to the rest of the application

    public function getByEmail(
        string $emailAddress,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.emailQueryHash = :email')
            ->setParameter('email', $this->makeEmailHash($emailAddress));
        return $qb->getQuery()->getOneOrNullResult($resultType);
    }

    public function createByEmail(string $emailAddress): User
    {
        // sometimes we need to be able to read the e-mail (to notify), so also encrypt it
        $emailNonce = \random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = \sodium_crypto_secretbox(
            $emailAddress,
            $emailNonce,
            $this->applicationConfig->getApplicationSecret()
        );

        $email = bin2hex($emailNonce) . '.' . bin2hex($cipher);

        $user = new User(
            ID::makeNewID(User::class),
            $this->makeEmailHash($emailAddress),
            $email,
            Bearing::getInitialRandomStepNumber()
        );

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    public function updateScoreRate(User $user, int $rateDelta = 0): void
    {
        $rate = $user->scoreRate + $rateDelta;
        $newScore = $this->currentScore($user);

        $user->score = $newScore;
        $user->scoreRate = $rate;
        $user->scoreCalculationTime = $this->currentTime;

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function updateScoreValue(User $user, int $scoreDelta = 0): void
    {
        $newScore = $this->currentScore($user) + $scoreDelta;

        $user->score = $newScore;
        $user->scoreCalculationTime = $this->currentTime;

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function currentScore(User $user): int
    {
        $currentScore = $user->score;
        $rate = $user->scoreRate;
        $previousTime = $user->scoreCalculationTime ?? $this->currentTime;

        $secondsDifference = $this->currentTime->getTimestamp() - $previousTime->getTimestamp();

        return max(0, $currentScore + ($secondsDifference * $rate));
    }

    public function fetchEmailAddress(UuidInterface $userId): string
    {
        $result = $this->getByID($userId);
        $parts = explode('.', $result['emailAddress']);

        return \sodium_crypto_secretbox_open(
            hex2bin($parts[1]),
            hex2bin($parts[0]),
            $this->applicationConfig->getApplicationSecret()
        );
    }

    private function makeEmailHash(string $emailAddress): string
    {
        // the e-mail address needs to be queryable so store a hash of it
        return \sodium_hex2bin(\hash_hmac(
            'sha256',
            $emailAddress,
            $this->applicationConfig->getApplicationSecret()
        ));
    }
}
