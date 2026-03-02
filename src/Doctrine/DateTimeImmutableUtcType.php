<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;

/**
 * Custom Doctrine type that extends {@see DateTimeImmutableType} to ensure all datetime values
 * are stored and retrieved in UTC timezone.
 *
 * This type guarantees timezone consistency by:
 * - Converting datetime values to UTC before persisting to the database
 * - Parsing and returning datetime values in UTC when reading from the database
 */
class DateTimeImmutableUtcType extends DateTimeImmutableType
{
    private static \DateTimeZone $utc;

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = $value->setTimezone(self::getUtc());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value->setTimezone(self::getUtc());
        }

        if ($value instanceof \DateTime) {
            throw InvalidType::new(
                $value,
                static::class,
                ['null', 'string', \DateTimeImmutable::class],
            );
        }

        $format = $platform->getDateTimeFormatString();

        $converted = \DateTimeImmutable::createFromFormat($format, $value, self::getUtc());

        if ($converted !== false) {
            return $converted;
        }

        try {
            return new \DateTimeImmutable($value, self::getUtc());
        } catch (\Exception $e) {
            throw InvalidFormat::new($value, static::class, $format, $e);
        }
    }

    private static function getUtc(): \DateTimeZone
    {
        return self::$utc ??= new \DateTimeZone('UTC');
    }
}
