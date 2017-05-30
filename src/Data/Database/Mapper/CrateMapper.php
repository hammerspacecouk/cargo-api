<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

use App\Domain\Entity\Crate;

class CrateMapper extends Mapper
{
    public function getCrate(array $item): Crate
    {
        $domainEntity = new Crate(
            $item['id'],
            $item['status'],
            $item['contents']
        );
        return $domainEntity;
    }
}
