<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Port;

class PortMapper extends Mapper
{
    public function getPort(array $item): Port
    {
        $blockadedBy = null;
        if (array_key_exists('blockadedBy', $item)) {
            $blockadedBy = $this->mapperFactory->createUserMapper()->getUser($item['blockadedBy']);
        }

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
            $item['blockadedUntil'],
            $blockadedBy
        );
    }
}
