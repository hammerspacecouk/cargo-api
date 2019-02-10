<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Crate;

class CrateMapper extends Mapper
{
    public function getCrate(array $item): Crate
    {
        return new Crate(
            $item['id'],
            $item['contents'],
            $item['value'],
        );
    }
}
