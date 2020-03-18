<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use function App\Functions\Numbers\average;

class Coordinate implements \JsonSerializable
{
    private int $x;
    private int $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    /** @deprecated  */
    public function a(): array
    {
        return [$this->x, $this->y]; // todo - temp. delete me
    }

    public function jsonSerialize(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
        ];
    }

    /**
     * Find the middle coordinate between this and the provided coordinate
     */
    public function middle(Coordinate $toCoords): Coordinate
    {
        return new Coordinate(
            (int)round(average($this->x, $toCoords->getX())),
            (int)round(average($this->y, $toCoords->getY())),
        );
    }
}
