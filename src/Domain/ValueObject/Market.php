<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Market implements \JsonSerializable
{
    private const MAX_VALUE = 100;

    private int $history;
    private int $discovery;
    private int $economy;
    private int $military;

    public function __construct(
        int $history,
        int $discovery,
        int $economy,
        int $military
    ) {
        $this->history = $history;
        $this->discovery = $discovery;
        $this->economy = $economy;
        $this->military = $military;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'history' => $this->history,
            'discovery' => $this->discovery,
            'economy' => $this->economy,
            'military' => $this->military,
        ];
    }

    public function getHistory(): int
    {
        return $this->history;
    }

    public function getDiscovery(): int
    {
        return $this->discovery;
    }

    public function getDiscoveryMultiplier(): float
    {
        return 1 - (($this->discovery / self::MAX_VALUE) * 0.75);
    }

    public function getMilitary(): int
    {
        return $this->military;
    }

    public function getMilitaryMultiplier(): float
    {
        return 1 + ($this->military / self::MAX_VALUE);
    }

    public function getEconomy(): int
    {
        return $this->economy;
    }

    public function getEconomyMultiplier(): float
    {
        return 1 - (($this->economy / self::MAX_VALUE) * 0.75);
    }
}
