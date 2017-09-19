<?php declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class Port extends Entity implements \JsonSerializable, CrateLocation
{
    private $name;

    public function __construct(
        UuidInterface $id,
        string $name
    ) {
        parent::__construct($id);
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
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
