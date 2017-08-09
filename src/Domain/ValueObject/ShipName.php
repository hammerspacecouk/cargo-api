<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject;

class ShipName implements \JsonSerializable
{
    private $first;
    private $second;

    public function __construct(
        string $first,
        string $second
    ) {
        $this->first = $first;
        $this->second = $second;
    }

    public function getParts(): array
    {
        return [
            $this->first,
            $this->second
        ];
    }

    public function getFullName(): string
    {
        $parts = array_merge(['The'], $this->getParts());
        return implode(' ', $parts);
    }

    public function getFirstWord(): string
    {
        return $this->first;
    }

    public function getSecondWord(): string
    {
        return $this->second;
    }

    public function jsonSerialize()
    {
        return [
            'full' => $this->getFullName(),
            'parts' => $this->getParts(),
        ];
    }

}
