<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class UserEffect extends Entity
{
    private ?Effect $effect;

    public function __construct(
        UuidInterface $id,
        Effect $effect = null
    ) {
        parent::__construct($id);
        $this->effect = $effect;
    }

    public function getEffect(): Effect
    {
        if ($this->effect === null) {
            throw new DataNotFetchedException('Effect was not fetched');
        }
        return $this->effect;
    }
}
