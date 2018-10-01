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

    public function __construct(
        int $portsVisited,
        PlayerRank $currentRank,
        PlayerRank $previousRank = null,
        PlayerRank $nextRank = null,
        AcknowledgePromotionToken $acknowledgePromotionToken = null
    ) {
        $this->portsVisited = $portsVisited;
        $this->currentRank = $currentRank;
        $this->nextRank = $nextRank;
        $this->previousRank = $previousRank;
        $this->acknowledgePromotionToken = $acknowledgePromotionToken;
    }

    public function jsonSerialize(): array
    {
        return [
            'portsVisited' => $this->portsVisited,
            'acknowledgeToken' => $this->acknowledgePromotionToken,
            'levelProgress' => $this->getLevelProgress(),
            'currentRank' => $this->currentRank,
            'previousRank' => $this->previousRank,
            'nextRank' => $this->nextRank,
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
        return (int)\floor(($distanceThrough / $gap) * 100);
    }

    public function getPortsVisited(): int
    {
        return $this->portsVisited;
    }

    public function getCurrentRank(): PlayerRank
    {
        return $this->currentRank;
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
