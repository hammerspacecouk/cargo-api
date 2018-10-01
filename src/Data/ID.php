<?php
declare(strict_types=1);

namespace App\Data;

use function App\Functions\Strings\shortHash;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

// todo - can this class be moved and/or better injected / mocked
class ID
{
    private const NAMESPACE = Uuid::NIL;

    public static function makeNewID(string $entityClass): UuidInterface
    {
        return self::markUuid(Uuid::uuid4(), $entityClass);
    }

    public static function makeIDFromKey(string $entityClass, string $key): UuidInterface
    {
        $uuid = Uuid::uuid5(self::NAMESPACE, sha1($key));
        return self::markUuid($uuid, $entityClass);
    }

    private static function markUuid(UuidInterface $uuid, string $entityClass): UuidInterface
    {
        $str = (string)$uuid;
        $marker = shortHash($entityClass, 4);
        $str = \substr_replace($str, $marker, 9, 4);
        return Uuid::fromString($str);
    }
}
