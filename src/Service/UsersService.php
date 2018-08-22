<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\Mapper\UserMapper;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\DeleteAccountToken;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UsersService extends AbstractService
{
    private $userMapper;

    public function getById(
        UuidInterface $uuid
    ): ?User {
        return $this->mapSingle(
            $this->entityManager->getUserRepo()->getByID($uuid)
        );
    }

    public function getByEmailAddress(EmailAddress $email): ?User
    {
        $userEntity = $this->entityManager->getUserRepo()
            ->getByQueryHash($this->makeContentHash((string)$email));
        if ($userEntity) {
            return $this->mapSingle($userEntity);
        }
        return null;
    }

    public function addEmailToUser(User $user, EmailAddress $email): void
    {
        /** @var DbUser $dbUser */
        $dbUser = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);
        $dbUser->queryHash = $this->makeContentHash((string)$email);
        // the user isn't anonymous any more, so remove the anonymous IP
        $dbUser->anonymousIpHash = null;
        $this->entityManager->persist($dbUser);
        $this->entityManager->flush();
    }

    public function getByAnonymousIp(string $ipAddress): ?User
    {
        $userEntity = $this->entityManager->getUserRepo()
            ->getByQueryHash($this->makeContentHash((string)$ipAddress));
        if ($userEntity) {
            return $this->mapSingle($userEntity);
        }
        return null;
    }

    public function cleanupIpHashes()
    {
        $before = $this->currentTime->sub(new \DateInterval('PT1H'));
        $this->entityManager->getUserRepo()->clearHashesBefore($before);
    }

    public function getOrCreateByEmailAddress(EmailAddress $email): User
    {
        $user = $this->getByEmailAddress($email);
        if ($user) {
            return $user;
        }

        $this->logger->notice('[NEW PLAYER] Creating a new player');

        $dbUser = new DbUser(Bearing::getInitialRandomStepNumber());
        $dbUser->queryHash = $this->makeContentHash((string) $email);
        $id = $this->newPlayer($dbUser);

        return $this->getById($id);
    }

    public function getNewAnonymousUser(string $ipAddress): User
    {
        $this->logger->notice('[NEW PLAYER] Creating a new anonymous user');
        $dbUser = new DbUser(Bearing::getInitialRandomStepNumber());
        $dbUser->anonymousIpHash = $this->makeContentHash($ipAddress);
        $id = $this->newPlayer($dbUser);
        return $this->getById($id);
    }

    public function makeDeleteAccountToken(UuidInterface $userId, int $stage): DeleteAccountToken
    {
        $token = $this->tokenHandler->makeToken(
            DeleteAccountToken::makeClaims(
                $userId,
                $stage
            ),
            DeleteAccountToken::TOKEN_TIME
        );
        return new DeleteAccountToken($token);
    }

    public function parseDeleteAccountToken(
        string $tokenString
    ): DeleteAccountToken {
        return new DeleteAccountToken($this->tokenHandler->parseTokenFromString($tokenString));
    }

    public function useStageTwoDeleteAccountToken(DeleteAccountToken $token): DeleteAccountToken
    {
        $this->tokenHandler->markAsUsed($token->getOriginalToken());
        return $this->makeDeleteAccountToken($token->getUserId(), 3);
    }

    public function useStageThreeDeleteAccountToken(DeleteAccountToken $token): void
    {
        $this->tokenHandler->markAsUsed($token->getOriginalToken());
        $this->entityManager->getUserRepo()->deleteById($token->getUserId(), DbUser::class);
    }

    public function cleanupActionTokens(DateTimeImmutable $expiredSince): int
    {
        return $this->entityManager->getUsedActionTokenRepo()->removeExpired($expiredSince);
    }

    private function newPlayer(DbUser $dbUser): UuidInterface
    {
        $safeHaven = $this->entityManager->getPortRepo()->getARandomSafePort(Query::HYDRATE_OBJECT);
        $starterShipClass = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();

        // start a transaction
        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Set the users original home port
            $dbUser->homePort = $safeHaven;

            // Make a new ship
            $ship = new DbShip(
                $shipName,
                $starterShipClass,
                $dbUser
            );

            // Put the ship into the home port
            $location = new DbShipLocation(
                $ship,
                $safeHaven,
                null,
                $this->currentTime
            );

            // Activate two crates - todo

            // Put crate 1 into the port - todo

            // Put crate 2 onto the ship - todo

            // Save everything
            $this->entityManager->persist($ship);
            $this->entityManager->persist($location);
            $this->entityManager->persist($dbUser);
            $this->entityManager->flush();

            // end the transaction

            $this->entityManager->getConnection()->commit();

            return $dbUser->id;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    private function makeContentHash(string $inputContent): string
    {
        return \bin2hex(\sodium_hex2bin(\hash_hmac(
            'sha256',
            (string)$inputContent,
            $this->applicationConfig->getApplicationSecret()
        )));
    }

    private function getMapper(): UserMapper
    {
        if (!$this->userMapper) {
            $this->userMapper = $this->mapperFactory->createUserMapper();
        }
        return $this->userMapper;
    }

    private function mapSingle(?array $result): ?User
    {
        if (!$result) {
            return null;
        }
        return $this->getMapper()->getUser($result);
    }

    /**
     * @return User[]
     */
    private function mapMany(array $results): array
    {
        return \array_map(['self', 'mapSingle'], $results);
    }
}
