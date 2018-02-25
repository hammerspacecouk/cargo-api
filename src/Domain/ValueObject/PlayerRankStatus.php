<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\PlayerRank;

class PlayerRankStatus implements \JsonSerializable
{
    private $portsVisited;
    private $currentRank;
    private $nextRank;
    private $previousRank;

    public function __construct(
        int $portsVisited,
        PlayerRank $currentRank,
        PlayerRank $previousRank = null,
        PlayerRank $nextRank = null
    ) {
        $this->portsVisited = $portsVisited;
        $this->currentRank = $currentRank;
        $this->nextRank = $nextRank;
        $this->previousRank = $previousRank;
    }

    public function jsonSerialize(): array
    {
        return [
            'portsVisited' => $this->portsVisited,
            'isRecentPromotion' => $this->isRecentPromotion(),
            'levelProgress' => $this->getLevelProgress(),
            'currentRank' => $this->currentRank,
            'previousRank' => $this->previousRank,
            'nextRank' => $this->nextRank,
        ];
    }

    public function isRecentPromotion(): bool
    {
        return $this->portsVisited == $this->currentRank->getThreshold();
    }

    public function getLevelProgress(): ?float
    {
        if (!$this->nextRank) {
            return null;
        }

        $start = $this->currentRank->getThreshold();
        $end = $this->nextRank->getThreshold();
        $gap = $end - $start;

        $distanceThrough  = $this->portsVisited - $start;
        return ($distanceThrough / $gap) * 100;
    }

    public function getPortsVisited(): int
    {
        return $this->portsVisited;
    }

    public function getCurrentRank(): PlayerRank
    {
        return $this->currentRank;
    }
}
