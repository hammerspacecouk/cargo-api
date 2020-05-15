<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class ActiveEffect extends Entity
{
    private ?Effect $effect;
    private DateTimeImmutable $expiry;
    private ?int $remainingCount;

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $expiry,
        ?int $remainingCount = null,
        Effect $effect = null
    ) {
        parent::__construct($id);
        $this->effect = $effect;
        $this->expiry = $expiry;
        $this->remainingCount = $remainingCount;
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
