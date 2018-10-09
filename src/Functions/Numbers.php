<?php
declare(strict_types=1);

namespace App\Functions\Numbers;

/**
 * Ensures an integer is between a maximum and a minimum
 * @param int $input
 * @param int $min
 * @param int $max
 * @return int
 */
function clamp(int $input, int $min, int $max): int
{
    return (int)max($min, min($input, $max));
}

/**
 * Checks if an integer is between a maximum and minimum (inclusive)
 * @param int $input
 * @param int $min
 * @param int $max
 * @return bool
 */
function isBetween(int $input, int $min, int $max): bool
{
    return ($input >= $min && $input <= $max);
}
