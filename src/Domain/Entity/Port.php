<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Null\NullUser;
use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Coordinate;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class Port extends Entity implements \JsonSerializable
{
    public const TOTAL_PORT_COUNT = 1000;

    private string $name;
    private bool $isSafe;
    private bool $isAHome;
    private array $coordinates;
    private bool $isGoal;
    private array $viewBoxes;
    private ?User $blockadedBy;
    private ?DateTimeImmutable $blockadedUntil;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafe,
        bool $isAHome,
        bool $isGoal,
        array $coordinates,
        array $viewBoxes,
        ?DateTimeImmutable $blockadedUntil,
        ?User $blockadedBy = null
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafe = $isSafe;
        $this->isAHome = $isAHome;
        $this->coordinates = $coordinates;
        $this->isGoal = $isGoal;
        $this->viewBoxes = $viewBoxes;
        $this->blockadedBy = $blockadedBy;
        $this->blockadedUntil = $blockadedUntil;
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
        $data = [
            'id' => $this->id,
            'type' => 'Port',
            'name' => $this->name,
            'isSafe' => $this->isSafe(),
        ];
        if ($this->blockadedBy !== null) {
            $data['blockade'] = null;
            if ($this->isBlockaded()) {
                $data['blockade'] = [
                    'player' => $this->blockadedBy,
                    'until' => DateTimeFactory::toJson($this->blockadedUntil)
                ];
            }
        }
        return $data;
    }

    public function isAHome(): bool
    {
        return $this->isAHome;
    }

    public function getBlockadedBy(): ?User
    {
        if ($this->blockadedBy === null) {
            throw new DataNotFetchedException(__METHOD__);
        }
        if ($this->blockadedBy instanceof NullUser) {
            return null;
        }
        return $this->blockadedBy;
    }

    public function getBlockadedUntil(): ?DateTimeImmutable
    {
        return $this->blockadedUntil;
    }

    public function isBlockaded(): bool
    {
        return (
            !$this->blockadedBy instanceof NullUser &&
            $this->blockadedUntil &&
            $this->blockadedUntil > DateTimeFactory::now()
        );
    }
}
