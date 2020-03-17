<?php
declare(strict_types=1);

namespace App\Service\Oauth;

use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\User;
use App\Service\UsersService;
use Doctrine\ORM\Query;

abstract class AbstractOAuthService extends UsersService implements OAuthServiceInterface
{
    public function getOrCreateUserForOAuthId(string $id): User
    {
        $oauthHash = $this->makeContentHash($id);
        $user = $this->getUserByOAuthHash($oauthHash);
        if ($user) {
            return $user;
        }
        return $this->newPlayer($oauthHash, null);
    }

    public function userExistsForOAuthId(string $id): bool
    {
        if (!$this instanceof OAuthServiceInterface) {
            throw new \RuntimeException('Called on a non-oauth instance');
        }
        $oauthHash = $this->makeContentHash($id);
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('COUNT(1)')
            ->where($this->getHashQuery())
            ->setParameter('hash', $oauthHash);
        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function attachToUser(User $user, string $id): User
    {
        $hash = $this->makeContentHash($id);
        $dbUser = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);

        $dbUser = $this->attachHash($dbUser, $hash);

        // the user isn't anonymous any more, so remove the anonymous IP
        $dbUser->anonymousIpHash = null;
        $this->entityManager->persist($dbUser);
        $this->entityManager->flush();

        $userWithId = $this->getUserByOAuthHash($hash);
        if (!$userWithId) {
            throw new \RuntimeException(__METHOD__ . ' went very wrong');
        }
        return $userWithId;
    }

    private function getUserByOAuthHash(
        string $oauthHash
    ): ?User {
        $qb = $this->getQueryBuilder(DbUser::class)
            ->select('tbl', 'r')
            ->join('tbl.lastRankSeen', 'r')
            ->where($this->getHashQuery())
            ->setParameter('hash', $oauthHash);
        $userEntity = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        if ($userEntity) {
            return $this->mapSingle($userEntity);
        }
        return null;
    }
}
