<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\ShipClass;
use Ramsey\Uuid\Uuid;

class Ship extends Entity implements \JsonSerializable
{
    private $name;
    private $shipClass;

    public function __construct(
        Uuid $id,
        string $name,
        ?ShipClass $shipClass
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->shipClass = $shipClass;
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

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'type' => 'Ship',
            'name' => $this->name,
        ];
        if ($this->shipClass) {
            $data['class'] = $this->shipClass;
        }
        return $data;
    }
}
