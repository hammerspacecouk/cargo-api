<?php
namespace App\Domain\ValueObject\Enum;

final class CrateStatus extends AbstractEnum
{
    public const INACTIVE = 'INACTIVE';
    public const ACTIVE = 'ACTIVE';
    public const DESTROYED = 'DESTROYED';
}
