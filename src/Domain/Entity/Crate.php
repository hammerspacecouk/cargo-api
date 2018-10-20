<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Null\NullEntity;
use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class Crate extends Entity implements \JsonSerializable
{
    private $contents;
    private $location;
    private $value;

    public function __construct(
        UuidInterface $id,
        string $contents,
        int $value,
        ?CrateLocation $location = null
    ) {
        parent::__construct($id);
        $this->contents = $contents;
        $this->value = $value;
        $this->location = $location;
    }

    public function getLocation(): ?CrateLocation
    {
        if (!$this->location) {
            throw new DataNotFetchedException(
                'Tried to use the crate location, but it was not fetched'
            );
        }
        if ($this->location instanceof NullEntity) {
            return null;
        }
        return $this->location;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getValuePerLightYear(int $distanceMultiplier): int
    {
        return $this->value * $distanceMultiplier;
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'value' => $this->value,
            'contents' => $this->contents,
        ];
        if ($this->location) {
            $data['location'] = $this->location;
        }
        return $data;
    }
}
