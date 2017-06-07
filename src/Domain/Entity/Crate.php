<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\Uuid;

class Crate extends Entity implements \JsonSerializable
{
    private $isDestroyed;
    private $contents;
    private $location;

    public function __construct(
        Uuid $id,
        string $contents,
        bool $isDestroyed = false,
        ?CrateLocation $location = null
    ) {
        parent::__construct($id);
        $this->contents = $contents;
        $this->location = $location;
        $this->isDestroyed = $isDestroyed;
    }

    public function getLocation()
    {
        if ($this->location === null) {
            throw new DataNotFetchedException(
                'Tried to use the crate location, but it was not fetched'
            );
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
