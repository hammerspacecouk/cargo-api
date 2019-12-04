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
use App\Domain\ValueObject\Token\DeleteAccountToken;
use App\Domain\ValueObject\Token\SimpleDataToken;
use DateInterval;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UsersService extends AbstractService
{
    private const DELETE_DELAY = 'PT15M';

    /**
     * @var UserMapper|null
     */
    private $userMapper;

    public function getById(
        UuidInterface $uuid
    ): ?User {
        return $this->mapSingle(
            $this->entityManager->getUserRepo()->findWithLastSeenRank($uuid)
        );
    }

    public function getLoginToken(string $type): SimpleDataToken
    {
        $token = $this->tokenHandler->makeToken(...SimpleDataToken::make(['login' => $type]));
        return new SimpleDataToken($token->getJsonToken(), (string)$token);
    }

    public function verifyLoginToken(string $tokenString, string $type): bool
    {
        // will throw if the token is expired or invalid
        $token = new SimpleDataToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
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
        $threshold = $this->dateTimeFactory->now()->sub(new DateInterval(self::DELETE_DELAY));
        return $user->getPlayStartTime() < $threshold;
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

    public function parseAcknowledgePromotionToken(
        string $tokenString
    ): AcknowledgePromotionToken {
        return new AcknowledgePromotionToken(
            $this->tokenHandler->parseTokenFromString($tokenString, false),
            $tokenString,
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

    protected function newPlayer(?string $oauthHash, ?string $ipHash): User
    {
        if (!($oauthHash xor $ipHash)) {
            throw new \DomainException('Must have either an email or ip hash to start');
        }

        // generate a random number seed for picking a home port and initial ship name
        // this is so that users can't keep deleting and recreating accounts to get new starting variables
        if ($ipHash) {
            // for anonymous users, add the ipHash to the date (so shared IPs are more unlikely to match)
            $seed = \crc32(\sha1($ipHash . $this->dateTimeFactory->now()->format('dd-MM-yyyy')));
        } else {
            // email based users will get the same outcome for that same email
            $seed = \crc32((string)$oauthHash);
        }
        \mt_srand($seed);

        // get some starting types
        $safeHaven = $this->entityManager->getPortRepo()->getARandomHomePort(Query::HYDRATE_OBJECT);
        $starterShipClass = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();
        $initialRank = $this->entityManager->getPlayerRankRepo()->getStarter(Query::HYDRATE_OBJECT);

        // start a transaction
        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Set the users original home port and persist the user
            $player = $this->entityManager->getUserRepo()->newPlayer(
                $ipHash,
                Colour::makeInitialRandomValue(),
                Bearing::getInitialRandomStepNumber(),
                $safeHaven,
                $initialRank,
                $oauthHash,
                $this
            );

            // make the player an initial ship and place it in the home port
            $ship = $this->entityManager->getShipRepo()->createNewShip($shipName, $starterShipClass, $player);
            $this->entityManager->getShipLocationRepo()->makeInPort($ship, $safeHaven, true);
            $this->entityManager->getPortVisitRepo()->recordVisit(null, $player, $safeHaven);

            // Make a crate (reserved for this player)
            $crate = $this->entityManager->getCrateRepo()->makeInitialCrateForPlayer($player);

            // Put the crate into the port
            $this->entityManager->getCrateLocationRepo()->makeInPort($crate, $safeHaven);

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
}
