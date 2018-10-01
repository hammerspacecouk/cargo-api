<?php
declare(strict_types=1);

namespace App\Functions\Transforms;

use InvalidArgumentException;

function csvToArray(string $filename): array
{
    if (!file_exists($filename) || !is_readable($filename)) {
        throw new InvalidArgumentException('File path not able to be opened');
    }
    $header = null;
    $data = [];

    $handle = fopen($filename, "r");
    if (!$handle) {
        throw new InvalidArgumentException('File path not able to be opened');
    }

    $row = fgetcsv($handle);
    while ($row !== false) {
        if (!$header) {
            $header = $row;
        } else {
            $data[] = array_combine($header, $row);
        }
        // next line
        $row = fgetcsv($handle);
    }
    fclose($handle);
    return $data;
}
