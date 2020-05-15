<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use function App\Functions\Transforms\rgbToHex;

class Colour implements \JsonSerializable
{
    private const INITIAL_BRIGHTNESS_MIN = 100;
    private const INITIAL_BRIGHTNESS_MAX = 200;

    private string $hex;

    public function __construct(
        string $hex
    ) {
        $this->hex = self::validateColour($hex);
    }

    public function __toString(): string
    {
        return $this->hex;
    }

    public function jsonSerialize(): string
    {
        return $this->hex;
    }

    public function getHex(): string
    {
        return $this->hex;
    }

    public static function makeInitialRandomValue(): self
    {
        $makeColour = function () {
            /** @noinspection RandomApiMigrationInspection - purposely want to use the seeded value */
            return \mt_rand(self::INITIAL_BRIGHTNESS_MIN, self::INITIAL_BRIGHTNESS_MAX);
        };

        $hex = rgbToHex($makeColour(), $makeColour(), $makeColour());

        return new self($hex);
    }

    public static function validateColour(string $input): string
    {
        $input = \strtolower($input);
        if (\strlen($input) === 6 && \ctype_xdigit($input)) {
            return $input;
        }
        throw new \InvalidArgumentException('"' . $input . '" is not a valid colour value');
    }
}
