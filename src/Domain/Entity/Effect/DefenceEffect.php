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

    public function isImmuneToPlague(): bool
    {
        return $this->getId()->equals(Uuid::fromString('2ea05787-3514-4cd8-b817-eab82fee9a1f')) ||
            $this->getId()->equals(Uuid::fromString('a11ec01b-2c51-42fa-b2dd-da6423d9c2ce'));
    }
}
