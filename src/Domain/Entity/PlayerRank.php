<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class PlayerRank extends Entity implements \JsonSerializable
{
    public const TRIAL_END_THRESHOLD = 175;
    public const TRIAL_END_THRESHOLD_MINUS_ONE = 120;

    private string $name;
    private int $threshold;
    private ?string $description;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $threshold,
        ?string $description
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->threshold = $threshold;
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getName(),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isTutorial(): bool
    {
        return $this->threshold === 0;
    }

    public function isTrialRange(): bool
    {
        return $this->threshold < self::TRIAL_END_THRESHOLD;
    }

    public function isNearTrialEnd(): bool
    {
        return $this->isTrialRange() && $this->threshold >= self::TRIAL_END_THRESHOLD_MINUS_ONE;
    }

    public function getSpeedMultiplier(): float
    {
        return $this->threshold / Port::TOTAL_PORT_COUNT;
    }

    public function meets(self $minimumRank): bool
    {
        return $this->threshold >= $minimumRank->getThreshold();
    }

    public function isAffectedByBlockades(): bool
    {
        return $this->threshold >= 120;
    }
}
