<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class Port extends Entity implements \JsonSerializable, CrateLocation
{
    private $name;
    private $isSafeHaven;

    public function __construct(
        UuidInterface $id,
        string $name,
        bool $isSafeHaven
    ) {
        parent::__construct($id);
        $this->name = $name;
        $this->isSafeHaven = $isSafeHaven;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSafeHaven(): bool
    {
        return $this->isSafeHaven;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => 'Port',
            'name' => $this->name,
            'safeHaven' => $this->isSafeHaven(),
        ];
    }
}
