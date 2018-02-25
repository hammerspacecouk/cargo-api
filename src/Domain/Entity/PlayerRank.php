<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class PlayerRank extends Entity implements \JsonSerializable
{
    private $name;
    private $threshold;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $threshold
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->threshold = $threshold;
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
}
