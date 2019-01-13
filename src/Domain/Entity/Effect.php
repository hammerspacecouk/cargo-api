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

    public function __construct(
        UuidInterface $id,
        string $name,
        string $description,
        int $cost,
        PlayerRank $minimumRank = null
    ) {
        parent::__construct($id);
        $this->minimumRank = $minimumRank;
        $this->name = $name;
        $this->cost = $cost;
        $this->description = $description;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
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

    public function getPurchaseCost(): int
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
}
