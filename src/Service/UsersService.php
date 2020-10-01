<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\EntityRepository\HintRepository;
use App\Data\Database\Mapper\UserMapper;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Bearing;
use App\Domain\ValueObject\Colour;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;
use App\Domain\ValueObject\Token\SimpleDataToken\AnonLoginToken;
use App\Domain\ValueObject\Token\DeleteAccountToken;
use App\Domain\ValueObject\Token\SimpleDataToken\ResetToken;
use App\Domain\ValueObject\Token\SimpleDataToken\SetNicknameToken;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UsersService extends AbstractService
{
    private const DELETE_DELAY = 'PT15M';

    private ?UserMapper $userMapper = null;

    public function getById(
        UuidInterface $uuid
    ): ?User {
        return $this->mapSingle(
            $this->entityManager->getUserRepo()->findWithLastSeenRank($uuid)
        );
    }

    public function getTopUsers(): array
    {
        return array_map(function ($result) {
            return $this->getMapper()->getUser($result);
        }, $this->entityManager->getUserRepo()->findTop());
    }

    public function getWinners(): array
    {
        return array_map(function ($result) {
            return [
                'player' => $this->getMapper()->getUser($result),
                'completionTime' => $result['gameCompletionTime'],
            ];
        }, $this->entityManager->getUserRepo()->findWinners());
    }

    public function getLoginToken(string $type): AnonLoginToken
    {
        $token = $this->tokenHandler->makeToken(...AnonLoginToken::make(['login' => $type]));
        return new AnonLoginToken($token->getJsonToken(), (string)$token);
    }

    public function verifyLoginToken(string $tokenString, string $type): bool
    {
        // will throw if the token is expired or invalid
        $token = new AnonLoginToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
        if ($token->getData()['login'] === $type) {
            return true;
        }
        throw new InvalidTokenException('Unexpected token type');
    }

    // phpcs:disable Squiz.PHP.CommentedOutCode.Found
    public function allowedToMakeAnonymousUser(?string $ipAddress): bool
    {
        return true; // todo - test IPs are coming through Cloudflare and ELB
//        if (!$ipAddress) {
//            $ipAddress = '';
//        }
//
//        $ipHash = $this->makeContentHash($ipAddress);
//        $max = $this->applicationConfig->getMaxUsersPerIp();
//        return (
//            $this->entityManager->getUserRepo()->countByIpHash($ipHash) < $max
//        );
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

    public function getUserHint(User $user): string
    {
        if ($user->isAnonymous()) {
            return HintRepository::ANON_HINT;
        }
        return $this->entityManager->getHintRepo()
            ->getRandomForRankThreshold($user->getRank()->getThreshold());
    }

    public function canUserDelete(User $user): bool
    {
        $threshold = DateTimeFactory::now()->sub(new DateInterval(self::DELETE_DELAY));
        return $user->getPlayStartTime() < $threshold;
    }

    public function getResetToken(User $user): ?string
    {
        // You can't reset your game until you've played for a little bit.
        // This is to prevent abuse.
        $hasStarterShip = $this->entityManager->getShipRepo()->userHasStarterShip($user->getId());
        if (!(
            !$user->isAnonymous() &&
            ($user->getRank()->getThreshold() > 10 || !$hasStarterShip)
        )) {
            return null;
        }
        $token = $this->tokenHandler->makeToken(...ResetToken::make(['id'=>$user->getId()]));
        return (string)new ResetToken($token->getJsonToken(), (string)$token);
    }

    public function getNicknameToken(User $user): ?string
    {
        if (!$user->canSetNickname()) {
            return null;
        }
        $token = $this->tokenHandler->makeToken(...SetNicknameToken::make([
            'id'=>$user->getId()
        ]));
        return (string)new SetNicknameToken($token->getJsonToken(), (string)$token);
    }

    public function makeDeleteAccountToken(UuidInterface $userId, int $stage): DeleteAccountToken
    {
        $token = $this->tokenHandler->makeToken(...DeleteAccountToken::make(
            $userId,
            $stage,
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

    public function parseResetToken(string $tokenString): UuidInterface
    {
        $token = new ResetToken($this->tokenHandler->parseTokenFromString($tokenString, false), $tokenString);
        return $this->uuidFactory->fromString($token->getData()['id']);
    }

    public function resetUser(User $user): void
    {
        /*
         * Delete all ships (should cascade to locations and crates)
         * Delete all port visits
         * Delete all user_effects
         * Delete all user_achievements
         * Delete all active_effects
         * Delete all events
         * Set User Score to 0 and rank to tutorial
         */
        $userEntity = $this->entityManager->getUserRepo()->getByIDWithHomePort($user->getId(), Query::HYDRATE_OBJECT);
        $initialRank = $this->entityManager->getPlayerRankRepo()->getStarter(Query::HYDRATE_OBJECT);
        $this->entityManager->getConnection()->transactional(function () use ($userEntity, $initialRank) {
            $this->entityManager->getShipRepo()->deleteByOwnerId($userEntity->id);
            $this->entityManager->getPortVisitRepo()->deleteForPlayerId($userEntity->id);
            $this->entityManager->getUserEffectRepo()->deleteForUserId($userEntity->id);
            $this->entityManager->getUserAchievementRepo()->deleteForUserId($userEntity->id);
            $this->entityManager->getActiveEffectRepo()->deleteForUserId($userEntity->id);
            $this->entityManager->getEventRepo()->deleteForUserId($userEntity->id);
            $this->entityManager->getUserRepo()->resetUser($userEntity, $initialRank);

            // all clean, start a new game
            $this->newGameSetup($userEntity);
        });
    }

    public function quickEditUser(UuidInterface $userId, array $fields): void
    {
        /** @var DbUser $userEntity */
        $userEntity = $this->entityManager->getUserRepo()->getByID($userId, Query::HYDRATE_OBJECT);

        foreach ($fields as $key => $value) {
            $userEntity->{$key} = $value === '' ? null : $value;
        }

        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    public function parseAcknowledgePromotionToken(
        string $tokenString
    ): AcknowledgePromotionToken {
        return new AcknowledgePromotionToken(
            $this->tokenHandler->parseTokenFromString($tokenString, false),
            $tokenString,
        );
    }

    public function useAcknowledgePromotionToken(
        AcknowledgePromotionToken $token,
        int $marketHistory,
        int $marketDiscovery,
        int $marketEconomy,
        int $marketMilitary
    ): void {
        $userId = $token->getUserId();
        $rankId = $token->getRankId();

        /** @var DbUser $userEntity */
        $userEntity = $this->entityManager->getUserRepo()->getByID($userId, Query::HYDRATE_OBJECT);
        $rankEntity = $this->entityManager->getPlayerRankRepo()->getByID($rankId, Query::HYDRATE_OBJECT);

        $userEntity->lastRankSeen = $rankEntity;
        $userEntity->marketEconomy = $marketEconomy;
        $userEntity->marketDiscovery = $marketDiscovery;
        $userEntity->marketHistory = $marketHistory;
        $userEntity->marketMilitary = $marketMilitary;

        $this->entityManager->getEventRepo()->logPromotion($userEntity, $rankEntity);
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    protected function newPlayer(?string $oauthHash, ?string $ipHash): User
    {
        if (!($oauthHash xor $ipHash)) {
            throw new \DomainException('Must have either an email or ip hash to start');
        }

        // generate a random number seed for picking a home port and initial ship name
        // this is so that users can't keep deleting and recreating accounts to get new starting variables
        if ($ipHash) {
            // for anonymous users, add the ipHash to the date (so shared IPs are more unlikely to match)
            $seed = \crc32(\sha1($ipHash . DateTimeFactory::now()->format('dd-MM-yyyy')));
        } else {
            // email based users will get the same outcome for that same email
            $seed = \crc32((string)$oauthHash);
        }
        \mt_srand($seed);

        // get some starting types
        $safeHaven = $this->entityManager->getPortRepo()->getARandomStarterPort(Query::HYDRATE_OBJECT);
        $initialRank = $this->entityManager->getPlayerRankRepo()->getStarter(Query::HYDRATE_OBJECT);

        // start a transaction
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $emblemColour = Colour::makeInitialRandomValue();

            /** @noinspection RandomApiMigrationInspection - purposely want to use the seeded value */
            $emblemFile = \dechex(\mt_rand(0, 15));
            $emblem = \file_get_contents(__DIR__ . '/../Data/Static/Emblems/' . $emblemFile . '.svg');
            if (!$emblem) {
                throw new \RuntimeException('Could not get file');
            }
            $emblem = \str_replace('#000000', '#' . $emblemColour, $emblem);

            // Set the users original home port and persist the user
            $player = $this->entityManager->getUserRepo()->newPlayer(
                $ipHash,
                $emblem,
                Bearing::getInitialRandomStepNumber(),
                $safeHaven,
                $initialRank,
                $oauthHash,
                $this
            );

            $this->newGameSetup($player);

            // end the transaction
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
        $user = $this->getById($player->id);
        if ($user) {
            return $user;
        }
        throw new \RuntimeException('Could not create or fetch user');
    }

    private function newGameSetup(DbUser $player): void
    {
        $starterShipClass = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();

        // make the player an initial ship and place it in the home port
        $ship = $this->entityManager->getShipRepo()->createNewShip($shipName, $starterShipClass, $player);
        $this->entityManager->getShipLocationRepo()->makeInPort($ship, $player->homePort, true);
        $this->entityManager->getPortVisitRepo()->recordVisit(null, $player, $player->homePort);

        // Make a crate (reserved for this player)
        $crate = $this->entityManager->getCrateRepo()->makeInitialCrateForPlayer($player);

        // Put the crate into the port
        $this->entityManager->getCrateLocationRepo()->makeInPort($crate, $player->homePort);
    }

    protected function makeContentHash(string $inputContent): string
    {
        return \bin2hex(\sodium_hex2bin(\hash_hmac(
            'sha256',
            $inputContent,
            $this->applicationConfig->getApplicationSecret(),
        )));
    }

    private function getMapper(): UserMapper
    {
        if (!$this->userMapper) {
            $this->userMapper = $this->mapperFactory->createUserMapper();
        }
        return $this->userMapper;
    }

    /**
     * @param array[]|null $result
     * @return User|null
     */
    protected function mapSingle(?array $result): ?User
    {
        if (!$result) {
            return null;
        }
        return $this->getMapper()->getUser($result);
    }

    public function setNickname(User $user, string $tokenString, string $nickname): void
    {
        $tokenData = new SetNicknameToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
        $id = $this->uuidFactory->fromString($tokenData->getData()['id'] ?? null);
        if (!$user->getId()->equals($id)) {
            throw new InvalidTokenException('Token not for this user');
        }

        /** @var DbUser $userEntity */
        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);
        $userEntity->nickname = $nickname;
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }
}
