<?php
declare(strict_types=1);

namespace App\Data\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumEffectsType extends Type
{
    public const NAME = 'enum_effects';

    public const TYPE_OFFENCE = 'OFFENCE';
    public const TYPE_DEFENCE = 'DEFENCE';
    public const TYPE_TRAVEL = 'TRAVEL';
    public const TYPE_SPECIAL = 'SPECIAL';
    public const TYPE_SHIELD = 'SHIELD';
    public const TYPE_BLOCKADE = 'BLOCKADE';

    public const ALL_TYPES = [
        self::TYPE_DEFENCE,
        self::TYPE_OFFENCE,
        self::TYPE_TRAVEL,
        self::TYPE_SPECIAL,
        self::TYPE_SHIELD,
        self::TYPE_BLOCKADE,
    ];

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'ENUM("' . \implode('","', self::ALL_TYPES) . '")';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!\in_array($value, self::ALL_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid enum type: ' . $value);
        }
        return $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
