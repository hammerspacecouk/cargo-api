<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Data\Database\Mapper\Traits\MinimumRankTrait;
use App\Data\Database\Types\EnumEffectsType;
use App\Domain\Entity\Effect;

class EffectMapper extends Mapper
{
    use MinimumRankTrait;

    public function getEffect(array $item): Effect
    {
        switch ($item['type']) {
            case EnumEffectsType::TYPE_OFFENCE:
                $effectClass = Effect\OffenceEffect::class;
                break;
            case EnumEffectsType::TYPE_DEFENCE:
                $effectClass = Effect\DefenceEffect::class;
                break;
            case EnumEffectsType::TYPE_TRAVEL:
                $effectClass = Effect\TravelEffect::class;
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }
        return new $effectClass(
            $item['id'],
            $item['name'],
            $item['description'],
            $item['purchaseCost'],
            $this->getMinimumRank($item),
        );
    }
}
