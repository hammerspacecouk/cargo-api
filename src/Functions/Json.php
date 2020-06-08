<?php
declare(strict_types=1);

namespace App\Functions\Json;

/**
 * A json_decode with this application's preferred settings
 * @param string $jsonString
 * @return array
 */
function jsonDecode(string $jsonString): array
{
    return \json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
}
