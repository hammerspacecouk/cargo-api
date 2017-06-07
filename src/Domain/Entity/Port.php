<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use Ramsey\Uuid\Uuid;

class Port extends Entity implements \JsonSerializable, CrateLocation, ShipLocation
{
    private $name;

    public function __construct(
        Uuid $id,
        string $name
    ) {
        parent::__construct($id);
        $this->name = $name;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => 'Port',
            'name' => $this->name,
        ];
    }
}
