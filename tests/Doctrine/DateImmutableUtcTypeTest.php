<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Doctrine;

use Dbp\Relay\CoreBundle\Doctrine\DateImmutableUtcType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateImmutableUtcTypeTest extends TestCase
{
    private AbstractPlatform&MockObject $platform;
    private DateImmutableUtcType $type;

    protected function setUp(): void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);
        $this->type = new DateImmutableUtcType();
    }

    public function testConvertsDateTimeImmutableInstanceToDatabaseValue(): void
    {
        $date = new \DateTimeImmutable('2016-01-01 12:34:56', new \DateTimeZone('UTC'));

        $this->platform->expects(self::once())
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        self::assertSame(
            '2016-01-01',
            $this->type->convertToDatabaseValue($date, $this->platform),
        );
    }

    public function testConvertsDateTimeImmutableInDifferentTimezoneToDatabaseValueInUtc(): void
    {
        // 2016-01-01 02:00:00 in +05:00 is 2015-12-31 21:00:00 UTC
        $date = new \DateTimeImmutable('2016-01-01 02:00:00', new \DateTimeZone('+05:00'));

        $this->platform->expects(self::once())
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        self::assertSame(
            '2015-12-31',
            $this->type->convertToDatabaseValue($date, $this->platform),
        );
    }

    public function testConvertsNullToDatabaseValue(): void
    {
        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    public function testDoesNotSupportMutableDateTimeToDatabaseValueConversion(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(new \DateTime(), $this->platform);
    }

    public function testConvertsDateTimeImmutableInstanceToPHPValueInUtc(): void
    {
        $date = new \DateTimeImmutable('2016-01-01 12:00:00', new \DateTimeZone('+05:00'));

        $result = $this->type->convertToPHPValue($date, $this->platform);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('UTC', $result->getTimezone()->getName());
        self::assertSame('2016-01-01 07:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function testConvertsNullToPHPValue(): void
    {
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testConvertsDateStringToPHPValue(): void
    {
        $this->platform->expects(self::once())
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        $date = $this->type->convertToPHPValue('2016-01-01', $this->platform);

        self::assertInstanceOf(\DateTimeImmutable::class, $date);
        self::assertSame('2016-01-01', $date->format('Y-m-d'));
        self::assertSame('UTC', $date->getTimezone()->getName());
    }

    public function testResetTimeFractionsWhenConvertingToPHPValue(): void
    {
        $this->platform
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        $date = $this->type->convertToPHPValue('2016-01-01', $this->platform);

        self::assertNotNull($date);
        self::assertSame('2016-01-01 00:00:00.000000', $date->format('Y-m-d H:i:s.u'));
    }

    public function testConvertedPHPValueAlwaysHasUtcTimezone(): void
    {
        $this->platform
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        $date = $this->type->convertToPHPValue('2016-06-15', $this->platform);

        self::assertNotNull($date);
        self::assertSame('UTC', $date->getTimezone()->getName());
    }

    public function testThrowsExceptionDuringConversionToPHPValueWithInvalidDateString(): void
    {
        $this->expectException(ConversionException::class);

        $this->platform
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        $this->type->convertToPHPValue('invalid date string', $this->platform);
    }

    public function testThrowsExceptionWhenConvertingMutableDateTimeToPHPValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(new \DateTime(), $this->platform);
    }

    public function testConvertToDatabaseValuePreservesUtcDate(): void
    {
        $date = new \DateTimeImmutable('2023-07-15 00:00:00', new \DateTimeZone('UTC'));

        $this->platform->expects(self::once())
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        self::assertSame(
            '2023-07-15',
            $this->type->convertToDatabaseValue($date, $this->platform),
        );
    }

    public function testConvertToPHPValueWithDateTimeImmutableAlreadyInUtc(): void
    {
        $date = new \DateTimeImmutable('2023-07-15 10:30:00', new \DateTimeZone('UTC'));

        $result = $this->type->convertToPHPValue($date, $this->platform);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('UTC', $result->getTimezone()->getName());
        self::assertSame('2023-07-15 10:30:00', $result->format('Y-m-d H:i:s'));
    }

    public function testRoundTripConversionPreservesDateInUtc(): void
    {
        $this->platform
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        $original = new \DateTimeImmutable('2023-12-25 00:00:00', new \DateTimeZone('UTC'));

        $dbValue = $this->type->convertToDatabaseValue($original, $this->platform);
        $phpValue = $this->type->convertToPHPValue($dbValue, $this->platform);

        self::assertNotNull($phpValue);
        self::assertSame('2023-12-25', $phpValue->format('Y-m-d'));
        self::assertSame('UTC', $phpValue->getTimezone()->getName());
    }

    public function testRoundTripConversionWithNonUtcTimezone(): void
    {
        $this->platform
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');

        // 2023-01-01 01:00:00 in +05:00 is 2022-12-31 20:00:00 UTC
        $original = new \DateTimeImmutable('2023-01-01 01:00:00', new \DateTimeZone('+05:00'));

        $dbValue = $this->type->convertToDatabaseValue($original, $this->platform);
        self::assertSame('2022-12-31', $dbValue);

        $phpValue = $this->type->convertToPHPValue($dbValue, $this->platform);

        self::assertNotNull($phpValue);
        self::assertSame('2022-12-31', $phpValue->format('Y-m-d'));
        self::assertSame('UTC', $phpValue->getTimezone()->getName());
    }
}
