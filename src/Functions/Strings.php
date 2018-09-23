<?php
declare(strict_types=1);

namespace App\Functions\Classes;

/**
 * Makes a short hash of strings for simple comparisons.
 * Not for cryptographic security
 * @param string $input
 * @param int $length
 * @return string
 */
function shortHash(string $input, $length = 4): string
{
    return \substr(\sha1($input), 0, $length);
}
