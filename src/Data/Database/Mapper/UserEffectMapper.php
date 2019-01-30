<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\UserEffect;

class UserEffectMapper extends Mapper
{
    public function getUserEffect(array $item): UserEffect
    {
        return new UserEffect(
            $item['id'],
            isset($item['effect']) ? $this->mapperFactory->createEffectMapper()->getEffect($item['effect']): null
        );
    }
}
