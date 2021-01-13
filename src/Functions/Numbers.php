<?php
declare(strict_types=1);

namespace App\Functions\Numbers;

use function array_sum;
use function count;
use function max;
use function min;

/**
 * Ensures an integer is between a maximum and a minimum
 */
function clamp(int $input, int $min, int $max): int
{
    return max($min, min($input, $max));
}

/**
 * Checks if an integer is between a maximum and minimum (inclusive)
 */
function isBetween(int $input, int $min, int $max): bool
{
    return ($input >= $min && $input <= $max);
}

/**
 * Returns the average of all the arguments provided
 */
function average(mixed ...$values): float
{
    return array_sum($values) / count($values);
}

/**
 * Returns the minimum of the two values,
 * but if the first value is null the second value will be returned (even if above zero)
 */
function minOf(?int $original, int $new): int
{
    if (!isset($original)) {
        return $new;
    }
    return min($original, $new);
}

/**
 * Returns the maximum of the two values,
 * but if the first value is null the second value will be returned (even if below zero)
 */
function maxOf(?int $original, int $new): int
{
    if (!isset($original)) {
        return $new;
    }
    return max($original, $new);
}
