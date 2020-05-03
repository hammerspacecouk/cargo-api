<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

abstract class Mapper
{
    protected MapperFactory $mapperFactory;

    public function __construct(
        MapperFactory $mapperFactory
    ) {
        $this->mapperFactory = $mapperFactory;
    }
}
