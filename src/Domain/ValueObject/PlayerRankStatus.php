<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\PlayerRank;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;
use JsonSerializable;

class PlayerRankStatus implements JsonSerializable
{
    private int $portsVisited;
    private PlayerRank $currentRank;
    private ?PlayerRank $nextRank;
    private ?PlayerRank $previousRank;
    private ?AcknowledgePromotionToken $acknowledgePromotionToken;
    private array $olderRanks;
    private ?int $latestCompletionTime;
    private ?int $bestCompletionTime;
    private ?int $leaderBoardPosition;

    public function __construct(
        int $portsVisited,
        PlayerRank $currentRank,
        PlayerRank $previousRank = null,
        PlayerRank $nextRank = null,
        array $olderRanks = [],
        AcknowledgePromotionToken $acknowledgePromotionToken = null,
        ?int $latestCompletionTime = null,
        ?int $bestCompletionTime = null,
        ?int $leaderBoardPosition = null
    ) {
        $this->portsVisited = $portsVisited;
        $this->currentRank = $currentRank;
        $this->nextRank = $nextRank;
        $this->previousRank = $previousRank;
        $this->acknowledgePromotionToken = $acknowledgePromotionToken;
        $this->olderRanks = $olderRanks;
        $this->latestCompletionTime = $latestCompletionTime;
        $this->bestCompletionTime = $bestCompletionTime;
        $this->leaderBoardPosition = $leaderBoardPosition;
    }

    public function jsonSerialize(): array
    {
        return [
            'portsVisited' => $this->portsVisited,
            'acknowledgeToken' => $this->acknowledgePromotionToken,
            'levelProgress' => $this->getLevelProgress(),
            'currentRank' => $this->currentRank,
            'previousRank' => $this->previousRank,
            'olderRanks' => $this->olderRanks,
            'nextRank' => $this->nextRank,
            'description' => $this->currentRank->getDescription(),
            'winState' => $this->getWinState(),
        ];
    }

    public function getLevelProgress(): ?int
    {
        if (!$this->nextRank) {
            return null;
        }

        $start = $this->currentRank->getThreshold();
        $end = $this->nextRank->getThreshold();
        $gap = ($end - $start);

        $distanceThrough = ($this->portsVisited - $start);
        return (int)round(($distanceThrough / $gap) * 100);
    }

    public function getPortsVisited(): int
    {
        return $this->portsVisited;
    }

    public function getCurrentRank(): PlayerRank
    {
        return $this->currentRank;
    }

    public function getNextRank(): ?PlayerRank
    {
        return $this->nextRank;
    }

    private function getWinState(): ?array
    {
        if (!$this->latestCompletionTime) {
            return null;
        }
        return [
            'completionTime' => $this->latestCompletionTime,
            'isPersonalBest' => $this->latestCompletionTime === $this->bestCompletionTime,
            'isWorldRecord' => $this->leaderBoardPosition === 1,
            'leaderboardPosition' => $this->leaderBoardPosition,
        ];
    }
}
