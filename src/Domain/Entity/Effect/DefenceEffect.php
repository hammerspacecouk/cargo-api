<?php
declare(strict_types=1);

namespace App\Domain\Entity\Effect;

use App\Domain\Entity\Effect;
use Ramsey\Uuid\Uuid;

class DefenceEffect extends Effect
{
    public function isInvisible(): bool
    {
        return $this->getId()->equals(Uuid::fromString('9a048983-afd7-439b-b89b-1f71bc7505fd'));
    }
}
