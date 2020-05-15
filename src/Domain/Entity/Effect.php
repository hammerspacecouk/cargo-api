<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Data\Database\Types\EnumEffectsDisplayGroupType;
use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

abstract class Effect extends Entity implements \JsonSerializable
{
    private ?PlayerRank $minimumRank;
    private string $name;
    private string $description;
    private ?int $cost;
    private ?int $durationSeconds;
    private ?int $hitCount;
    private string $displayGroup;
    private int $sortOrder;
    /**
     * @var array<mixed>|null
     */
    protected ?array $value;

    public function __construct(
        UuidInterface $id,
        string $name,
        string $description,
        string $displayGroup,
        int $sortOrder,
        int $cost = null,
        ?int $durationSeconds = null,
        ?int $hitCount = null,
        ?array $value = null,
        PlayerRank $minimumRank = null
    ) {
        parent::__construct($id);
        $this->minimumRank = $minimumRank;
        $this->name = $name;
        $this->cost = $cost;
        $this->description = $description;
        $this->durationSeconds = $durationSeconds;
        $this->hitCount = $hitCount;
        $this->value = $value;
        $this->displayGroup = $displayGroup;
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->displayGroup,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDisplayGroup(): string
    {
        return $this->displayGroup;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function canBePurchased(): bool
    {
        return $this->cost !== null;
    }

    public function canBeUsedInPort(): bool
    {
        return true;
    }

    public function canBeUsedInChannel(): bool
    {
        return $this->displayGroup === EnumEffectsDisplayGroupType::TYPE_DEFENCE;
    }

    public function getPurchaseCost(): ?int
    {
        return $this->cost;
    }

    public function getMinimumRank(): PlayerRank
    {
        if (!$this->minimumRank) {
            throw new DataNotFetchedException(
                'Tried to use getMinimumRank, but it was not fetched'
            );
        }
        return $this->minimumRank;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function getHitCount(): ?int
    {
        return $this->hitCount;
    }

    public function getValue(): ?array
    {
        return $this->value;
    }

    public function sortCompare(Effect $effect): int
    {
        // returns -1 if this instance should be before $effect
        $positions = array_flip(EnumEffectsDisplayGroupType::ALL_TYPES);
        $compare = $positions[$this->getDisplayGroup()] <=> $positions[$effect->getDisplayGroup()];
        if ($compare === 0) {
            $compare = $this->getSortOrder() <=> $effect->getSortOrder(); // https://youtu.be/7TYJyCCO8Dc?t=41
        }
        return $compare;
    }
}
