<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\Mapper\UserMapper;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Domain\ValueObject\Colour;
use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;
use App\Domain\ValueObject\Token\DeleteAccountToken;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UsersService extends AbstractService
{
    public const IP_HASH_LIFETIME = 'PT1H';

    private const DELETE_DELAY = 'PT15M';

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

    public function addEmailToUser(User $user, EmailAddress $email): User
    {
        /** @var DbUser $dbUser */
        $dbUser = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);
        $dbUser->queryHash = $this->makeContentHash((string)$email);
        // the user isn't anonymous any more, so remove the anonymous IP
        $dbUser->anonymousIpHash = null;
        $this->entityManager->persist($dbUser);
        $this->entityManager->flush();

        $userWithEmail = $this->getByEmailAddress($email);
        if (!$userWithEmail) {
            throw new \RuntimeException(__METHOD__ . ' went very wrong');
        }
        return $userWithEmail;
    }

    public function getByAnonymousIp(string $ipAddress): ?User // todo - am I using this?
    {
        $userEntity = $this->entityManager->getUserRepo()
            ->getByQueryHash($this->makeContentHash($ipAddress));
        if ($userEntity) {
            return $this->mapSingle($userEntity);
        }
        return null;
    }

    public function getOrCreateByEmailAddress(EmailAddress $email): User
    {
        $this->logger->notice('[NEW PLAYER] [EMAIL]');
        $user = $this->getByEmailAddress($email);
        if ($user) {
            return $user;
        }
        $queryHash = $this->makeContentHash((string)$email);
        return $this->newPlayer($queryHash, null);
    }

    public function allowedToMakeAnonymousUser(?string $ipAddress): bool
    {
        if (!$ipAddress) {
            $ipAddress = ''; // todo - remember to check IPs are coming through Cloudflare and ELB
        }

        $ipHash = $this->makeContentHash($ipAddress);
        $max = $this->applicationConfig->getMaxUsersPerIp();
        return (
            $this->entityManager->getUserRepo()->countByIpHash($ipHash) < $max
        );
    }

    public function getNewAnonymousUser(?string $ipAddress): User
    {
        $this->logger->notice('[NEW PLAYER] [ANONYMOUS]');
        $ipHash = null;
        if ($ipAddress) {
            $ipHash = $this->makeContentHash($ipAddress);
        }
        return $this->newPlayer(null, $ipHash);
    }

    public function canUserDelete(User $user): bool
    {
        $threshold = $this->dateTimeFactory->now()->sub(new DateInterval(self::DELETE_DELAY));
        return $user->getPlayStartTime() < $threshold;
    }

    public function makeDeleteAccountToken(UuidInterface $userId, int $stage): DeleteAccountToken
    {
        $token = $this->tokenHandler->makeToken(...DeleteAccountToken::make(
            $userId,
            $stage
        ));
        return new DeleteAccountToken($token->getJsonToken(), (string)$token);
    }

    public function parseDeleteAccountToken(
        string $tokenString
    ): DeleteAccountToken {
        return new DeleteAccountToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
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

    public function parseAcknowledgePromotionToken(
        string $tokenString
    ): AcknowledgePromotionToken {
        return new AcknowledgePromotionToken(
            $this->tokenHandler->parseTokenFromString($tokenString, false),
            $tokenString
        );
    }

    public function useAcknowledgePromotionToken(AcknowledgePromotionToken $token): void
    {
        $userId = $token->getUserId();
        $rankId = $token->getRankId();

        /** @var DbUser $userEntity */
        $userEntity = $this->entityManager->getUserRepo()->getByID($userId, Query::HYDRATE_OBJECT);
        $rankEntity = $this->entityManager->getPlayerRankRepo()->getByID($rankId, Query::HYDRATE_OBJECT);

        $userEntity->lastRankSeen = $rankEntity;
        $this->entityManager->getEventRepo()->logPromotion($userEntity, $rankEntity);
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    private function newPlayer(?string $queryHash, ?string $ipHash): User
    {
        if (!($queryHash xor $ipHash)) {
            throw new \DomainException('Must have either an email or ip hash to start');
        }

        // generate a random number seed for picking a home port and initial ship name
        // this is so that users can't keep deleting and recreating accounts to get new starting variables
        if ($ipHash) {
            // for anonymous users, add the ipHash to the date (so shared IPs are more unlikely to match)
            $seed = crc32(sha1($ipHash . $this->dateTimeFactory->now()->format('dd-MM-yyyy')));
        } else {
            // email based users will get the same outcome for that same email
            $seed = crc32($queryHash);
        }
        mt_srand($seed);

        // get some starting types
        $safeHaven = $this->entityManager->getPortRepo()->getARandomSafePort(Query::HYDRATE_OBJECT);
        $starterShipClass = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();
        $initialRank = $this->entityManager->getPlayerRankRepo()->getStarter(Query::HYDRATE_OBJECT);

        // start a transaction
        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Set the users original home port and persist the user
            $player = $this->entityManager->getUserRepo()->newPlayer(
                $queryHash,
                $ipHash,
                Colour::makeInitialRandomValue(),
                Bearing::getInitialRandomStepNumber(),
                $safeHaven,
                $initialRank
            );

            // make the player an initial ship and place it in the home port
            $ship = $this->entityManager->getShipRepo()->createNewShip($shipName, $starterShipClass, $player);
            $this->entityManager->getShipLocationRepo()->makeInPort($ship, $safeHaven);
            $this->entityManager->getPortVisitRepo()->recordVisit($player, $safeHaven);

            // Make a crate (reserved for this player)
            $crate = $this->entityManager->getCrateRepo()->newRandomCrateForPlayer($player);

            // Put the crate into the port
            $this->entityManager->getCrateLocationRepo()->makeInPort($crate, $safeHaven);

            // end the transaction
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
        if ($user = $this->getById($player->id)) {
            return $user;
        }
        throw new \RuntimeException('Could not create or fetch user');
    }

    private function makeContentHash(string $inputContent): string
    {
        return \bin2hex(\sodium_hex2bin(\hash_hmac(
            'sha256',
            $inputContent,
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
