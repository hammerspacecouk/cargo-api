<?php
declare(strict_types=1);

namespace App\Functions\Arrays;

function seedableRandomItem(array $array)
{
    $max = count($array) - 1;
    /** @noinspection RandomApiMigrationInspection - because it needs to be seedable */
    return $array[mt_rand(0, $max)];
}
