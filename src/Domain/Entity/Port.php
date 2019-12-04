<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class Port extends Entity implements \JsonSerializable
{
    public const TOTAL_PORT_COUNT = 1000;

    private $name;
    private $isSafe;
    private $isAHome;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafe,
        bool $isAHome
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafe = $isSafe;
        $this->isAHome = $isAHome;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => 'Port',
            'name' => $this->name,
            'isSafe' => $this->isSafe(),
        ];
    }

    public function isAHome(): bool
    {
        return $this->isAHome;
    }
}
