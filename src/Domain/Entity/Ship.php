<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use function sha1;

class Ship extends Entity implements \JsonSerializable
{
    private string $name;
    private ?ShipClass $shipClass;
    private ?ShipLocation $location;
    private ?User $owner;
    private int $strength;
    private ?UuidInterface $convoyId;
    private bool $hasPlague;
    private DateTimeImmutable $launchTime;
    private int $originalPurchaseCost;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $strength,
        bool $hasPlague,
        DateTimeImmutable $launchTime,
        int $originalPurchaseCost,
        ?UuidInterface $convoyId,
        ?User $owner,
        ?ShipClass $shipClass,
        ?ShipLocation $location = null
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->shipClass = $shipClass;
        $this->location = $location;
        $this->owner = $owner;
        $this->strength = $strength;
        $this->convoyId = $convoyId;
        $this->hasPlague = $hasPlague;
        $this->launchTime = $launchTime;
        $this->originalPurchaseCost = $originalPurchaseCost;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function getStrengthPercent(): int
    {
        return (int)(($this->strength / $this->getShipClass()->getStrength()) * 100);
    }

    public function isDestroyed(): bool
    {
        return $this->strength <= 0;
    }

    public function isFullStrength(): bool
    {
        return $this->getStrengthPercent() === 100;
    }

    public function isHealthy(): bool
    {
        return $this->getStrengthPercent() > 25;
    }

    public function getConvoyId(): ?UuidInterface
    {
        return $this->convoyId;
    }

    public function isInConvoy(): bool
    {
        return $this->convoyId !== null;
    }

    public function hasPlague(): bool
    {
        return $this->hasPlague;
    }

    public function meetsStrength(int $minimumStrength): bool
    {
        return $this->strength >= $minimumStrength;
    }

    public function getLocation(): ShipLocation
    {
        if ($this->location === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship location, but it was not fetched'
            );
        }
        return $this->location;
    }

    public function getOwner(): User
    {
        if ($this->owner === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship owner, but it was not fetched'
            );
        }
        return $this->owner;
    }

    public function getShipClass(): ShipClass
    {
        if ($this->shipClass === null) {
            throw new DataNotFetchedException(
                'Tried to use the ship ShipClass, but it was not fetched'
            );
        }
        return $this->shipClass;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'Ship',
            'name' => $this->name,
            'launchDate' => $this->launchTime->format(DateTimeFactory::FULL),
            'isDestroyed' => $this->isDestroyed(),
            'convoyId' => $this->convoyId,
            'hasPlague' => $this->hasPlague,
        ];
        if ($this->owner) {
            $data['owner'] = $this->owner;
        }
        if ($this->shipClass) {
            $data['shipClass'] = $this->shipClass;
            $data['strengthPercent'] = $this->getStrengthPercent();
        }
        if ($this->location) {
            $data['location'] = $this->location;
        }
        return $data;
    }

    public function toHash(): string
    {
        return sha1($this->id->toString());
    }

    public function isProbe(): bool
    {
        return $this->getShipClass()->isProbe();
    }

    public function isStarterShip(): bool
    {
        return $this->getShipClass()->isStarterShip();
    }

    public function calculateValue(DateTimeImmutable $now): int
    {
        // the value of the ship decreases with age and damage
        // loses 1% of the purchase cost per day + initial 5%
        $days = 100 - min(5 + $now->diff($this->launchTime)->days, 99);
        $value = ($this->originalPurchaseCost / 100) * $days;
        // directly match the shield strength percent
        $value = $value / 100 * $this->getStrengthPercent();
        return max(1, (int)ceil($value));
    }
}
