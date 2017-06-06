<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

use App\Domain\Entity\Crate;
use App\Domain\ValueObject\Enum\CrateStatus;

class CrateMapper extends Mapper
{
    public function getCrate(array $item): Crate
    {
        $domainEntity = new Crate(
            $item['id'],
            new CrateStatus($item['status']),
            $item['contents']
        );
        return $domainEntity;
    }
}
