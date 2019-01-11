<?php
declare(strict_types=1);

namespace App\Domain\Entity;


abstract class Effect extends Entity implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return [];
    }
}
