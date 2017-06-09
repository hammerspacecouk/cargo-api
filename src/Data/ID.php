<?php
declare(strict_types = 1);
namespace App\Data;

use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipClass;
use App\Data\Database\Entity\ShipLocation;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ID
{
    private const ENTITY_MAPPINGS = [
        Crate::class => 'ca4e',
        CrateLocation::class => 'dd57',
        Port::class => '03fe',
        Ship::class => '3909',
        ShipClass::class => '5b3b',
        ShipLocation::class => '7abc',
    ];

    public static function makeNewID($entityClass)
    {
        $id = Uuid::uuid4();
        $str = (string) $id;
        $str = substr_replace($str, self::ENTITY_MAPPINGS[$entityClass], 9, 4);
        return Uuid::fromString($str);
    }

    public static function getIDType(UuidInterface $id)
    {
        $part = (string) substr((string) $id, 9, 4);
        $map = array_flip(self::ENTITY_MAPPINGS);
        if ($map[$part]) {
            return $map[$part];
        }
        throw new InvalidArgumentException('Id not recognised');
    }
}
