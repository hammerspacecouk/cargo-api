<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Port;

class PortMapper extends Mapper
{
    public function getPort(array $item): Port
    {
        return new Port(
            $item['id'],
            $item['name'],
            $item['isSafeHaven'],
            $item['isAHome'],
            $item['isDestination'],
            array_map(static function ($r) {
                return $r['c'] ?? [];
            }, $item['coordinates']),
            array_map(static function ($r) {
                return $r['v'] ?? [];
            }, $item['coordinates']),
        );
    }
}
