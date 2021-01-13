<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class Crate extends Entity implements \JsonSerializable
{
    public function __construct(
        UuidInterface $id,
        private string $contents,
        private int $value
    ) {
        parent::__construct($id);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getValuePerLightYear(int $distanceMultiplier): int
    {
        return $this->value * $distanceMultiplier;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'contents' => $this->contents,
        ];
    }
}
