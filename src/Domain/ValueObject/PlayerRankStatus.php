<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\PlayerRank;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;

class PlayerRankStatus implements \JsonSerializable
{
    private $portsVisited;
    private $currentRank;
    private $nextRank;
    private $previousRank;
    private $acknowledgePromotionToken;
    private $olderRanks;

    public function __construct(
        int $portsVisited,
        PlayerRank $currentRank,
        PlayerRank $previousRank = null,
        PlayerRank $nextRank = null,
        array $olderRanks = [],
        AcknowledgePromotionToken $acknowledgePromotionToken = null
    ) {
        $this->portsVisited = $portsVisited;
        $this->currentRank = $currentRank;
        $this->nextRank = $nextRank;
        $this->previousRank = $previousRank;
        $this->acknowledgePromotionToken = $acknowledgePromotionToken;
        $this->olderRanks = $olderRanks;
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
        ];
    }

    public function getLevelProgress(): ?float
    {
        if (!$this->nextRank) {
            return null;
        }

        $start = $this->currentRank->getThreshold();
        $end = $this->nextRank->getThreshold();
        $gap = ($end - $start);

        $distanceThrough = ($this->portsVisited - $start);
        return $distanceThrough / $gap;
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

    public function isTutorial(): bool
    {
        return $this->getPortsVisited() === 0;
    }

    public function getAcknowledgePromotionToken(): ?AcknowledgePromotionToken
    {
        return $this->acknowledgePromotionToken;
    }
}
