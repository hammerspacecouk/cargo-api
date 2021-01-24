<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\PlayerRankStatus;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Achievement extends Entity implements JsonSerializable
{
    public function __construct(
        UuidInterface $id,
        private string $name,
        private string $description,
        private string $svg,
        private ?DateTimeImmutable $collectedAt = null
    ) {
        parent::__construct($id);
    }

    public static function getPseudoMissionForPlanets(PlayerRankStatus $rankStatus): self
    {
        $nextCount = 0;
        if ($rankStatus->getNextRank()) {
            $nextCount = $rankStatus->getNextRank()->getThreshold();
        }
        $diff = $nextCount - $rankStatus->getCurrentRank()->getThreshold();
        $progress = $rankStatus->getPortsVisited() - $rankStatus->getCurrentRank()->getThreshold();
        $progressString = '';
        if ($progress > 0) {
            $progressString = ' (' . $progress . '/' . $diff . ')';
        }
        return new self(
            Uuid::fromString(Uuid::NIL),
            (string)$nextCount,
            'Discover ' . $diff . ' new planet' . ($diff > 1 ? 's' : '') . $progressString,
            '',
        );
    }

    public function isCollected(): bool
    {
        return !!$this->collectedAt;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'collectedAt' => DateTimeFactory::toJson($this->collectedAt),
        ];
    }

    public function getSvg(): string
    {
        return $this->svg;
    }
}
