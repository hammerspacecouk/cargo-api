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
    private bool $isGoal;
    private array $viewBoxes;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafe,
        bool $isAHome,
        bool $isGoal,
        array $coordinates,
        array $viewBoxes
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafe = $isSafe;
        $this->isAHome = $isAHome;
        $this->coordinates = $coordinates;
        $this->isGoal = $isGoal;
        $this->viewBoxes = $viewBoxes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    public function isGoal(): bool
    {
        return $this->isGoal;
    }

    public function getViewBox(int $rotationStep): string
    {
        return $this->viewBoxes[$rotationStep];
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
