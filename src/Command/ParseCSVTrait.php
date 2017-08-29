<?php
declare(strict_types=1);
namespace App\Command;

use InvalidArgumentException;

trait ParseCSVTrait
{
    protected function csvToArray($filename): array
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

        while (($row = fgetcsv($handle)) !== false) {
            if (!$header) {
                $header = $row;
            } else {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
        return $data;
    }
}
