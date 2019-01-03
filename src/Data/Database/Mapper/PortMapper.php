<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Port;

class PortMapper extends Mapper
{
    public function getPort(array $item): Port
    {
        $domainEntity = new Port(
            $item['id'],
            $item['name'],
            $item['isSafeHaven'],
        );
        return $domainEntity;
    }
}
