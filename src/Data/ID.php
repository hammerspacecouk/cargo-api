<?php declare(strict_types=1);

namespace App\Data;

use App\Data\Database\Entity\Channel;
use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\Dictionary;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\PlayerStanding;
use App\Data\Database\Entity\PortVisit;
use App\Data\Database\Entity\Token;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\ShipClass;
use App\Data\Database\Entity\ShipLocation;
use App\Data\Database\Entity\User;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ID
{
    private const ENTITY_MAPPINGS = [
        Channel::class => 'bd99',
        Crate::class => 'ca4e',
        CrateLocation::class => 'dd57',
        Dictionary::class => 'dddd',
        Token::class => 'ffff',
        PlayerRank::class => 'abcd',
        PlayerStanding::class => 'fedc',
        Port::class => '03fe',
        PortVisit::class => '1010',
        Ship::class => '3909',
        ShipClass::class => '5b3b',
        ShipLocation::class => '7abc',
        User::class => '0000',
    ];

    public static function makeNewID($entityClass): UuidInterface
    {
        if (!isset(self::ENTITY_MAPPINGS[$entityClass])) {
            throw new InvalidArgumentException($entityClass . ' not in the list of entity mappings');
        }

        $id = Uuid::uuid4();
        $str = (string)$id;
        $str = substr_replace($str, self::ENTITY_MAPPINGS[$entityClass], 9, 4);
        return Uuid::fromString($str);
    }

    public static function getIDType(UuidInterface $id)
    {
        $part = (string)substr((string)$id, 9, 4);
        $map = array_flip(self::ENTITY_MAPPINGS);
        if ($map[$part]) {
            return $map[$part];
        }
        throw new InvalidArgumentException('Id not recognised');
    }
}
