<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class Crate extends Entity implements \JsonSerializable
{
    private $contents;
    private $value;

    public function __construct(
        UuidInterface $id,
        string $contents,
        int $value
    ) {
        parent::__construct($id);
        $this->contents = $contents;
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getValuePerLightYear(int $distanceMultiplier): int
    {
        return $this->value * $distanceMultiplier;
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'value' => $this->value,
            'contents' => $this->contents,
        ];
        return $data;
    }
}
