<?php
declare(strict_types=1);

namespace App\Data\Database\Types;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Field type mapping for the Doctrine Database Abstraction Layer (DBAL).
 * Will store with microseconds in the database and read back out
 */
class DateTimeMicrosecondType extends DateTimeImmutableType
{
    /**
     * @var string
     */
    private const NAME = 'datetime_microsecond';

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'DATETIME(6)';
    }

    /**
     * @inheritdoc
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (\is_object($value) &&
            $value instanceof \DateTimeImmutable &&
            ($platform instanceof PostgreSqlPlatform || $platform instanceof MySqlPlatform)
        ) {
            $dateTimeFormat = $platform->getDateTimeFormatString();
            return $value->format("{$dateTimeFormat}.u");
        }
        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }
}
