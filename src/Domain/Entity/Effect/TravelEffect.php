<?php
declare(strict_types=1);

namespace App\Domain\Entity\Effect;

use App\Domain\Entity\Effect;

class TravelEffect extends Effect
{
    public function getSpeedDivisor(): int
    {
        return $this->value['s'] ?? 1;
    }

    public function isInstant(): bool
    {
        return $this->value && $this->value['s'] === -1;
    }

    public function getEarningsMultiplier(): int
    {
        return $this->value['e'] ?? 1;
    }
}
