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

    public const ALL_TYPES = [
      self::TYPE_DEFENCE,
      self::TYPE_OFFENCE,
      self::TYPE_TRAVEL,
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
