<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

abstract class Effect extends Entity implements \JsonSerializable
{
    private $minimumRank;
    private $name;
    private $description;
    private $cost;
    private $durationSeconds;
    private $hitCount;
    private $displayGroup;
    protected $value;

    public function __construct(
        UuidInterface $id,
        string $name,
        string $description,
        string $displayGroup,
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
    }

    public function jsonSerialize()
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

    public function canBePurchased(): bool
    {
        return $this->cost !== null;
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
}
