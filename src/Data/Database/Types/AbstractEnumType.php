<?php
declare(strict_types=1);

namespace App\Data\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class AbstractEnumType extends Type
{
    private const NAME = '';
    private const ALL_TYPES = [];

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'ENUM("' . \implode('","', static::ALL_TYPES) . '")';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!\in_array($value, static::ALL_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid enum type: ' . $value);
        }
        return $value;
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
