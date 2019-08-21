<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\ActiveEffect;

class ActiveEffectMapper extends Mapper
{
    public function getActiveEffect(array $item): ActiveEffect
    {
        return new ActiveEffect(
            $item['id'],
            $item['expiry'],
            $item['remainingCount'],
            isset($item['effect']) ? $this->mapperFactory->createEffectMapper()->getEffect($item['effect']): null
        );
    }
}
