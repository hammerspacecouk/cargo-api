<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class ActiveEffect extends Entity
{
    public function __construct(
        UuidInterface $id,
        private DateTimeImmutable $expiry,
        private ?int $remainingCount = null,
        private ?Effect $effect = null
    ) {
        parent::__construct($id);
    }

    public function getEffect(): Effect
    {
        if ($this->effect === null) {
            throw new DataNotFetchedException('Effect was not fetched');
        }
        return $this->effect;
    }

    public function getExpiry(): ?DateTimeImmutable
    {
        // expiry only means something if the original effect is time-based (otherwise it's just for cleanup)
        if ($this->getEffect()->getDurationSeconds()) {
            return $this->expiry;
        }
        return null;
    }

    public function getRemainingCount(): ?int
    {
        return $this->remainingCount;
    }
}
