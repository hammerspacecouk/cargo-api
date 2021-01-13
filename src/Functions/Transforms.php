<?php
declare(strict_types=1);

namespace App\Functions\Transforms;

use InvalidArgumentException;

function csvToArray(string $filename): array
{
    $header = null;
    $data = [];

    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        $handle = \tmpfile();
        $sourceData = \file_get_contents($filename);
        if (!$sourceData || !$handle) {
            throw new \RuntimeException('Could not get data');
        }
        \fwrite($handle, $sourceData);
        \fseek($handle, 0);
    } else {
        if (!\file_exists($filename) || !\is_readable($filename)) {
            throw new InvalidArgumentException('File path not able to be opened');
        }
        $handle = \fopen($filename, 'rb');
    }

    if (!$handle) {
        throw new InvalidArgumentException('File path not able to be opened');
    }

    $row = \fgetcsv($handle);
    while ($row) {
        if (!$header) {
            $header = $row;
        } else {
            $data[] = \array_combine($header, $row);
        }
        // next line
        $row = \fgetcsv($handle);
    }
    \fclose($handle);
    return $data;
}

/**
 * Converts three colour components into a hex colour (without prefixed #)
 */
function rgbToHex(
    int $red,
    int $green,
    int $blue
): string {
    return \sprintf('%02x%02x%02x', $red, $green, $blue);
}
