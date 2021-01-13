<?php
declare(strict_types=1);

namespace App\Functions\Strings;

/**
 * Makes a short hash of strings for simple comparisons.
 * Not for cryptographic security
 */
function shortHash(string $input, int $length = 4): string
{
    return \substr(\sha1($input), 0, $length);
}

/**
 * implode, but removing any nulls from the array first
 */
function filteredImplode(string $glue, array $pieces): string
{
    $pieces = \array_filter($pieces);
    return \implode($glue, $pieces);
}
