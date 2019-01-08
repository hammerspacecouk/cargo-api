<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\ValueObject\Effect;

class EffectMapper extends Mapper
{
    public function getEffect(array $item): Effect
    {
        // todo
        return new Effect(
        );
    }

    public function toDatabase(Effect $effect)
    {
        // todo - this might be interesting
    }
}
