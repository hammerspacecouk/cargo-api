<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Coordinate;
use Ramsey\Uuid\UuidInterface;

class Port extends Entity implements \JsonSerializable
{
    public const TOTAL_PORT_COUNT = 1000;

    private string $name;
    private bool $isSafe;
    private bool $isAHome;
    private string $viewBox;
    private array $coordinates;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafe,
        bool $isAHome,
        string $viewBox,
        array $coordinates
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafe = $isSafe;
        $this->isAHome = $isAHome;
        $this->viewBox = $viewBox;
        $this->coordinates = $coordinates;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    public function getViewBox(): string
    {
        return $this->viewBox;
    }

    public function getCoordinates(int $rotationStep): Coordinate
    {
        return new Coordinate(
            $this->coordinates[$rotationStep][0],
            $this->coordinates[$rotationStep][1],
        );
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
