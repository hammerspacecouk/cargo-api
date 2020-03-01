<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Bearing implements \JsonSerializable
{
    // key -> value = opposite
    private const BEARINGS = [
        'NW' => 'SE',
        'NE' => 'SW',
        'E' => 'W',
        'SE' => 'NW',
        'SW' => 'NE',
        'W' => 'E',
    ];

    private const CANVAS_DEGREES_FROM_HORIZON = [
        'NW' => -60,
        'NE' => -60,
        'E' => 0,
        'SE' => 60,
        'SW' => 60,
        'W' => 0,
    ];

    private $bearing;

    public function __construct(
        string $bearing
    ) {
        $bearing = self::validate($bearing);
        $this->bearing = $bearing;
    }

    public static function validate(string $bearing): string
    {
        $bearing = \strtoupper($bearing);
        if (!\in_array($bearing, self::BEARINGS, true)) {
            throw new \InvalidArgumentException('Not a valid bearing (' . $bearing . ')');
        }
        return $bearing;
    }

    public static function getInitialRandomStepNumber(): int
    {
        /** @noinspection RandomApiMigrationInspection - due to wanting to use the seeded value */
        return \mt_rand(0, \count(self::BEARINGS) - 1);
    }

    public static function getEmptyBearingsList(): array
    {
        return \array_fill_keys(\array_keys(self::BEARINGS), null);
    }

    public static function getRotatedBearing(string $bearing, int $steps): string
    {
        $bearing = self::validate($bearing);
        $bearings = \array_keys(self::BEARINGS);
        $key = \array_search($bearing, $bearings, true);
        $newKey = ($key + $steps);
        $indexCount = \count($bearings);

        return $bearings[$newKey % $indexCount];
    }

    public function getRotated(int $steps): Bearing
    {
        $newBearing = self::getRotatedBearing($this->bearing, $steps);
        return new Bearing($newBearing);
    }

    public function getOpposite(): Bearing
    {
        return new Bearing(self::BEARINGS[$this->bearing]);
    }

    public function getDegreesFromHorizon(): int
    {
        return self::CANVAS_DEGREES_FROM_HORIZON[$this->bearing];
    }

    public function getXMultiplier(): int
    {
        if (\in_array($this->bearing, ['NW', 'W', 'SW'])) {
            return -1;
        }
        return 1;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->bearing;
    }
}
