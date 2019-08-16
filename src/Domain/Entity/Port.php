<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class Port extends Entity implements \JsonSerializable
{
    public const TOTAL_PORT_COUNT = 1000;

    private $name;
    private $isSafe;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafe
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafe = $isSafe;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => 'Port',
            'name' => $this->name,
            'isSafe' => $this->isSafe(),
        ];
    }
}
