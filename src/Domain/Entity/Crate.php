<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Null\NullEntity;
use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class Crate extends Entity implements \JsonSerializable
{
    private $contents;
    private $location;
    private $isDestroyed;

    public function __construct(
        UuidInterface $id,
        string $contents,
        bool $isDestroyed = false,
        ?CrateLocation $location = null
    ) {
        parent::__construct($id);
        $this->contents = $contents;
        $this->location = $location;
        $this->isDestroyed = $isDestroyed;
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

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'type' => 'Crate',
            'isDestroyed' => $this->isDestroyed,
            'contents' => $this->contents,
        ];
        if ($this->location) {
            $data['location'] = $this->location;
        }
        return $data;
    }
}
