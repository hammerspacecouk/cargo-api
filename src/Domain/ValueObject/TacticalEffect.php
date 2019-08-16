<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\Effect;
use App\Domain\Entity\PlayerRank;
use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use DateTimeImmutable;

class TacticalEffect implements \JsonSerializable
{
    private $effect;
    private $minimumRank;
    private $currentCount;
    private $hitsRemaining;
    private $activeExpiry;
    private $actionToken;
    private $shipSelect;
    private $purchaseEffectTransaction;

    public function __construct(
        ?Effect $effect,
        PlayerRank $minimumRank,
        bool $shipSelect = false,
        int $currentCount = 0,
        ?int $hitsRemaining = 0,
        DateTimeImmutable $activeExpiry = null,
        AbstractActionToken $actionToken = null,
        Transaction $purchaseEffectTransaction = null
    ) {

        $this->effect = $effect;
        $this->minimumRank = $minimumRank;
        $this->currentCount = $currentCount;
        $this->hitsRemaining = $hitsRemaining;
        $this->activeExpiry = $activeExpiry;
        $this->actionToken = $actionToken;
        $this->shipSelect = $shipSelect;
        $this->purchaseEffectTransaction = $purchaseEffectTransaction;
    }

    public function jsonSerialize()
    {
        if (!$this->effect) {
            return ['minimumRank' => $this->minimumRank];
        }

        return [
            'effect' => $this->effect,
            'currentCount' => $this->currentCount,
            'actionToken' => $this->actionToken,
            'hitsRemaining' => $this->hitsRemaining,
            'expiry' => $this->activeExpiry ? $this->activeExpiry->format('c') : null,
            'isActive' => $this->activeExpiry || $this->hitsRemaining,
            'mustSelectShip' => $this->shipSelect,
            'purchaseToken' => $this->purchaseEffectTransaction,
        ];
    }
}
