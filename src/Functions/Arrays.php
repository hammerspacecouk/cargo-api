<?php
declare(strict_types=1);

namespace App\Functions\Arrays;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function mt_rand;

function ensureArray(mixed $data): array
{
    if (!is_array($data)) {
        $data = [$data];
    }
    return $data;
}

function find(callable $checkFunction, array $inputArray): mixed
{
    foreach ($inputArray as $input) {
        if ($checkFunction($input)) {
            return $input;
        }
    }
    return null;
}

function seedableRandomItem(array $array): mixed
{
    $max = count($array) - 1;
    /** @noinspection RandomApiMigrationInspection - because it needs to be seedable */
    return $array[mt_rand(0, $max)];
}

// takes the first of each item that matches the value
function groupByValue(array $inputArray, callable $valueFinder): array
{
    // Build an array of keys containing unique keys
    $uniques = array_unique(array_map($valueFinder, $inputArray));

    // Remove the duplicates from original array and reset keys
    return array_values(array_filter($inputArray, function ($key) use ($uniques) {
        return array_key_exists($key, $uniques);
    }, ARRAY_FILTER_USE_KEY));
}

function firstItem(array $array): mixed
{
    return $array[array_key_first($array)];
}

/**
 * Runs a map over an array, filters out nulls and resets the indexes
 */
function filteredMap(array $array, callable $callback): array
{
    return array_values(array_filter(array_map($callback, $array)));
}
