<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\User;
use App\Domain\ValueObject\PlayerRankStatus;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class PlayerRanksService extends AbstractService
{
    public function getById(UuidInterface $uuid): ?PlayerRank
    {
        $result = $this->entityManager->getPlayerRankRepo()->getByID($uuid);
        if (!$result) {
            return null;
        }
        return $this->mapperFactory->createPlayerRankMapper()
            ->getPlayerRank($result);
    }

    /**
     * @return PlayerRank[]
     */
    public function getList(): array
    {
        $results = $this->entityManager->getPlayerRankRepo()->getList();
        $mapper = $this->mapperFactory->createPlayerRankMapper();
        return array_map(static function ($result) use ($mapper) {
            return $mapper->getPlayerRank($result);
        }, $results);
    }

    public function getForUser(User $user): PlayerRankStatus
    {
        $portVisitCount = $this->entityManager->getPortVisitRepo()->countForPlayerId($user->getId());
        $allRanks = $this->getList();
        /** @var PlayerRank[] $allRanks */
        $previousRank = null;
        $currentRank = null;
        $nextRank = null;
        $olderRanks = [];
        foreach ($allRanks as $rank) {
            if ($rank->getThreshold() <= $portVisitCount) {
                if ($previousRank) {
                    \array_unshift($olderRanks, $previousRank);
                }
                $previousRank = $currentRank;
                $currentRank = $rank;
            } else {
                $nextRank = $rank; // can only run once, when ranks no longer match
                break;
            }
        }

        if (!$currentRank) {
            throw new \LogicException('Could not calculate a rank');
        }

        $hasAcknowledgedPromotion = false;
        $rankEntity = $this->entityManager->getUserRepo()->findWithLastSeenRank($user->getId());
        if ($rankEntity && $currentRank->getId()->equals($rankEntity['lastRankSeen']['id'])) {
            $hasAcknowledgedPromotion = true;
        }

        $acknowledgeToken = null;
        $availableCredits = null;
        if ($portVisitCount > 1 && !$hasAcknowledgedPromotion) {
            $availableCredits = $currentRank->getMarketCredits();

            // make a token to acknowledge the promotion
            $token = $this->tokenHandler->makeToken(...AcknowledgePromotionToken::make(
                $user->getId(),
                $currentRank->getId(),
                $availableCredits
            ));
            $acknowledgeToken = new AcknowledgePromotionToken(
                $token
            );
        }

        $latestCompletionTime = null;
        $bestCompletionTime = null;
        $position = null;
        if (!$nextRank) {
            // user is in the win state
            // get their stats. get their position on the leaderboard
            /** @var DbUser $rawUser */
            $rawUser = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);
            $latestCompletionTime = $rawUser->gameCompletionTime;
            if ($latestCompletionTime) {
                $bestCompletionTime = $rawUser->bestCompletionTime;
                $position = 1 + $this->entityManager->getUserRepo()->countFasterFinishers($bestCompletionTime);
            }
        }

        return new PlayerRankStatus(
            $portVisitCount,
            $currentRank,
            $previousRank,
            $nextRank,
            $olderRanks,
            $acknowledgeToken,
            $latestCompletionTime,
            $bestCompletionTime,
            $position,
            $availableCredits,
            $user->getMarket(),
        );
    }
}
