<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\PlayerRankStatus;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Achievement extends Entity implements \JsonSerializable
{
    private $name;
    private $description;
    private $svg;
    private $collectedAt;

    public function __construct(
        UuidInterface $id,
        string $name,
        string $description,
        string $svg,
        \DateTimeImmutable $collectedAt = null
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->description = $description;
        $this->svg = $svg;
        $this->collectedAt = $collectedAt;
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

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'collectedAt' => $this->collectedAt ? $this->collectedAt->format('c') : null,
        ];
    }

    public function getSvg(): string
    {
        return $this->svg;
    }
}
