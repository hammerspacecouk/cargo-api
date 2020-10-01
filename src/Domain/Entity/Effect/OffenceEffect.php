<?php
declare(strict_types=1);

namespace App\Domain\Entity\Effect;

use App\Domain\Entity\Effect;

class OffenceEffect extends Effect
{
    public function getDamage(float $multiplier = 1): int
    {
        return (int)round($this->value['damage'] * $multiplier);
    }

    public function affectsAllShips(): bool
    {
        return $this->value && $this->value['all'];
    }
}
