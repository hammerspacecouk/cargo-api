<?php
declare(strict_types=1);

namespace App\Domain\Entity\Effect;

use App\Domain\Entity\Effect;

class TravelEffect extends Effect
{
    public function getSpeedDivisor(): ?int
    {
        if (!$this->value) {
            return null;
        }
        return $this->value['s'];
    }

    public function isInstant(): bool
    {
      return $this->value && $this->value['s'] === -1;
    }

    public function getEarningsMultiplier(): ?int
    {
        if (!$this->value) {
            return null;
        }
        return $this->value['e'];
    }
}
