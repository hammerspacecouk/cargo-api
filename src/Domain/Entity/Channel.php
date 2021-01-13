<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Bearing;
use Ramsey\Uuid\UuidInterface;

class Channel extends Entity implements \JsonSerializable
{
    public function __construct(
        UuidInterface $id,
        private Port $origin,
        private Port $destination,
        private Bearing $bearing,
        private int $distance,
        private int $minimumStrength,
        private ?PlayerRank $minimumRank = null
    ) {
        parent::__construct($id);
    }

    public function getOrigin(): Port
    {
        return $this->origin;
    }

    public function getDestination(Port $startingPort = null): Port
    {
        if ($startingPort && $this->isReversed($startingPort)) {
            return $this->origin;
        }
        return $this->destination;
    }

    public function getBearing(Port $startingPort = null): Bearing
    {
        if ($startingPort && $this->isReversed($startingPort)) {
            return $this->bearing->getOpposite();
        }
        return $this->bearing;
    }

    public function getDistance(): int
    {
        return $this->distance;
    }

    public function getMinimumStrength(): int
    {
        return $this->minimumStrength;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): ?array
    {
        // must not be exposed without explicit consent
        return null;
    }

    public function getMinimumRank(): PlayerRank
    {
        if (!$this->minimumRank) {
            throw new DataNotFetchedException(
                'Tried to use the channel minimum rank, but it was not fetched'
            );
        }
        return $this->minimumRank;
    }

    public function isReversed(Port $startingPort): bool
    {
        return !$this->getOrigin()->equals($startingPort);
    }
}
