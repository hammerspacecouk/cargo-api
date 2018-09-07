<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\User;
use App\Domain\ValueObject\PlayerRankStatus;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;

class PlayerRanksService extends AbstractService
{
    public function getList(): array
    {
        $results = $this->entityManager->getPlayerRankRepo()->getList();
        $mapper = $this->mapperFactory->createPlayerRankMapper();
        return array_map(function ($result) use ($mapper) {
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
        foreach ($allRanks as $rank) {
            if ($rank->getThreshold() <= $portVisitCount) {
                $previousRank = $currentRank;
                $currentRank = $rank;
                continue;
            }

            $nextRank = $rank; // can only run once, when ranks no longer match
            break;
        }

        $hasAcknowledgedPromotion = false;
        $rankEntity = $this->entityManager->getUserRepo()->findWithLastSeenRank($user->getId());
        if ($rankEntity && $currentRank->getId()->equals($rankEntity['lastRankSeen']['id'])) {
            $hasAcknowledgedPromotion = true;
        }

        $acknowledgeToken = null;
        if ($portVisitCount > 1 && !$hasAcknowledgedPromotion) {
            // make a token to acknowledge the promotion
            $acknowledgeToken = new AcknowledgePromotionToken(
                $this->tokenHandler->makeToken(
                    AcknowledgePromotionToken::makeClaims(
                        $user->getId(),
                        $currentRank->getId()
                    ),
                    'PT1H'
                ));
        }

        return new PlayerRankStatus(
            $portVisitCount,
            $currentRank,
            $previousRank,
            $nextRank,
            $acknowledgeToken
        );
    }
}
