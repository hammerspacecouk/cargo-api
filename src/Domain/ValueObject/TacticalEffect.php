<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\ActiveEffect;
use App\Domain\Entity\Effect;
use App\Domain\Entity\UserEffect;
use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use DateTimeImmutable;

class TacticalEffect implements \JsonSerializable
{
    private $effect;
    private $currentCount;
    private $hitsRemaining;
    private $activeExpiry;
    private $actionToken;
    private $shipSelect;
    private $userEffect;
    private $isActive;
    private $activeEffect;

    public function __construct(
        ?Effect $effect,
        bool $isActive = false,
        ?UserEffect $userEffect = null,
        ?ActiveEffect $activeEffect = null,
        bool $shipSelect = false,
        int $currentCount = 0,
        ?int $hitsRemaining = 0,
        DateTimeImmutable $activeExpiry = null,
        AbstractActionToken $actionToken = null
    ) {

        $this->effect = $effect;
        $this->currentCount = $currentCount;
        $this->hitsRemaining = $hitsRemaining;
        $this->activeExpiry = $activeExpiry;
        $this->actionToken = $actionToken;
        $this->shipSelect = $shipSelect;
        $this->userEffect = $userEffect;
        $this->isActive = $isActive;
        $this->activeEffect = $activeEffect;
    }

    public function jsonSerialize()
    {
        return [
            'effect' => $this->effect,
            'currentCount' => $this->currentCount,
            'actionToken' => $this->actionToken,
            'hitsRemaining' => $this->hitsRemaining,
            'expiry' => $this->activeExpiry ? $this->activeExpiry->format('c') : null,
            'isActive' => $this->isActive,
            'mustSelectShip' => $this->shipSelect,
        ];
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getEffect(): ?Effect
    {
        return $this->effect;
    }

    public function getActiveEffect(): ?ActiveEffect
    {
        return $this->activeEffect;
    }

    public function getUserEffect(): ?UserEffect
    {
        return $this->userEffect;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }

    public function isAvailableShipOffence(): bool
    {
        return (
          $this->shipSelect && // must be for a single ship
          $this->userEffect && // must have one to use
          $this->effect instanceof Effect\OffenceEffect
        );
    }
}
