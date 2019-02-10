<?php
declare(strict_types=1);

namespace App\Functions\Strings;

/**
 * Makes a short hash of strings for simple comparisons.
 * Not for cryptographic security
 * @param string $input
 * @param int $length
 * @return string
 */
function shortHash(string $input, int $length = 4): string
{
    return \substr(\sha1($input), 0, $length);
}

/**
 * Says if a string begins with another string
 * @param string $needle
 * @param string $haystack
 * @return bool
 */
function startsWith(string $needle, string $haystack): bool
{
    return \strpos($haystack, $needle) === 0;
}

/**
 * implode, but removing any nulls from the array first
 * @param string $glue
 * @param array $pieces
 * @return string
 */
function filteredImplode(string $glue, array $pieces): string
{
    $pieces = \array_filter($pieces);
    return \implode($glue, $pieces);
}
