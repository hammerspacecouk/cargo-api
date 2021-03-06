<?php
declare(strict_types=1);

namespace App\Data\Database\Types;

class EnumEffectsType extends AbstractEnumType
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
}
