<?php
declare(strict_types = 1);
namespace App\Data\Database\Mapper;

class MapperFactory
{
    public function createCrateMapper(): CrateMapper
    {
        return new CrateMapper($this);
    }

    public function createPortMapper(): PortMapper
    {
        return new PortMapper($this);
    }
}
