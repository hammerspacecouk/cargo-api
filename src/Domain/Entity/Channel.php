<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Bearing;
use Ramsey\Uuid\UuidInterface;

class Channel extends Entity implements \JsonSerializable
{
    private $origin;
    private $destination;
    private $bearing;
    private $distance;

    public function __construct(
        UuidInterface $id,
        Port $origin,
        Port $destination,
        Bearing $bearing,
        int $distance
    ) {
        parent::__construct($id);
        $this->origin = $origin;
        $this->destination = $destination;
        $this->bearing = $bearing;
        $this->distance = $distance;
    }

    public function getOrigin(): Port
    {
        return $this->origin;
    }


    public function getDestination(): Port
    {
        return $this->destination;
    }

    public function getBearing(): Bearing
    {
        return $this->bearing;
    }

    public function getDistance(): int
    {
        return $this->distance;
    }

    public function jsonSerialize()
    {
        // must not be exposed without explicit consent
        return null;
    }
}
