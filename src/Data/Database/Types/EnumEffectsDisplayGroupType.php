<?php
declare(strict_types=1);

namespace App\Data\Database\Types;

class EnumEffectsDisplayGroupType extends AbstractEnumType
{
    public const NAME = 'enum_effect_display_group';

    public const TYPE_OFFENCE = 'OFFENCE';
    public const TYPE_DEFENCE = 'DEFENCE';
    public const TYPE_TRAVEL = 'TRAVEL';
    public const TYPE_SPECIAL = 'SPECIAL';

    public const ALL_TYPES = [
        self::TYPE_DEFENCE,
        self::TYPE_TRAVEL,
        self::TYPE_OFFENCE,
        self::TYPE_SPECIAL,
    ];
}
