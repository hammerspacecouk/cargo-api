<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Colour;
use Ramsey\Uuid\UuidInterface;

class PlayerRank extends Entity implements \JsonSerializable
{
    private $name;
    private $threshold;
    private $emblem;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $threshold,
        string $emblem
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->threshold = $threshold;
        $this->emblem = $emblem;
    }

    public function jsonSerialize()
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

    public function isTutorial(): bool
    {
        return $this->threshold === 0;
    }

    public function getEmblem(?Colour $playerColour): string
    {
        $emblem = $this->emblem;
        if ($playerColour) {
            $targetColour = 'fefefe';
            $emblem = \str_replace($targetColour, $playerColour, $this->emblem);
        }
        return $emblem;
    }

    public function getSpeedMultiplier(): float
    {
        return $this->threshold / Port::TOTAL_PORT_COUNT;
    }

    public function meets(self $minimumRank): bool
    {
        return $this->threshold >= $minimumRank->getThreshold();
    }
}
