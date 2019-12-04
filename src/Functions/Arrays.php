<?php
declare(strict_types=1);

namespace App\Functions\Arrays;

/**
 * @param mixed $data
 * @return array
 */
function ensureArray($data): array
{
    if (!is_array($data)) {
        $data = [$data];
    }
    return $data;
}

/**
 * @param callable $checkFunction
 * @param array $inputArray
 * @return mixed|null
 */
function find(callable $checkFunction, array $inputArray)
{
    foreach ($inputArray as $input) {
        if ($checkFunction($input)) {
            return $input;
        }
    }
    return null;
}

/**
 * @param array $array
 * @return mixed
 */
function seedableRandomItem(array $array)
{
    $max = \count($array) - 1;
    /** @noinspection RandomApiMigrationInspection - because it needs to be seedable */
    return $array[\mt_rand(0, $max)];
}

// takes the first of each item that matches the value
function groupByValue(array $inputArray, callable $valueFinder): array
{
    // Build an array of keys containing unique keys
    $uniques = \array_unique(array_map($valueFinder, $inputArray));

    // Remove the duplicates from original array and reset keys
    return \array_values(\array_filter($inputArray, function ($key) use ($uniques) {
        return \array_key_exists($key, $uniques);
    }, ARRAY_FILTER_USE_KEY));
}
