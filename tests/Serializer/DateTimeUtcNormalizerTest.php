<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Serializer;

use Dbp\Relay\CoreBundle\Serializer\DateTimeUtcNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

class DateTimeUtcNormalizerTest extends TestCase
{
    private DateTimeUtcNormalizer $normalizer;
    private array $optInContext;

    protected function setUp(): void
    {
        $this->normalizer = new DateTimeUtcNormalizer();
        $this->optInContext = [DateTimeUtcNormalizer::CONTEXT_KEY => true];
    }

    // -------------------------------------------------------------------------
    // supportsNormalization
    // -------------------------------------------------------------------------

    public function testSupportsNormalizationForDateTimeImmutableWithContextKey(): void
    {
        $dt = new \DateTimeImmutable();

        self::assertTrue($this->normalizer->supportsNormalization($dt, context: $this->optInContext));
    }

    public function testSupportsNormalizationForDateTimeWithContextKey(): void
    {
        $dt = new \DateTime();

        self::assertTrue($this->normalizer->supportsNormalization($dt, context: $this->optInContext));
    }

    public function testSupportsNormalizationReturnsFalseWithoutContextKey(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(new \DateTimeImmutable()));
    }

    public function testSupportsNormalizationReturnsFalseForNonDateTimeInterface(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass(), context: $this->optInContext));
    }

    // -------------------------------------------------------------------------
    // normalize
    // -------------------------------------------------------------------------

    public function testNormalizesUtcDateTimeImmutableWithoutMilliseconds(): void
    {
        $dt = new \DateTimeImmutable('2024-01-01T12:00:00', new \DateTimeZone('UTC'));

        self::assertSame('2024-01-01T12:00:00.000Z', $this->normalizer->normalize($dt));
    }

    public function testNormalizesUtcDateTimeImmutableWithMilliseconds(): void
    {
        $dt = new \DateTimeImmutable('2024-01-01T12:00:00.123000', new \DateTimeZone('UTC'));

        self::assertSame('2024-01-01T12:00:00.123Z', $this->normalizer->normalize($dt));
    }

    public function testNormalizesNonUtcDateTimeImmutableToUtc(): void
    {
        // 15:00:00 UTC+5 → 10:00:00 UTC
        $dt = new \DateTimeImmutable('2024-01-01T15:00:00', new \DateTimeZone('+05:00'));

        self::assertSame('2024-01-01T10:00:00.000Z', $this->normalizer->normalize($dt));
    }

    public function testNormalizesNonUtcDateTimeImmutableWithMillisecondsToUtc(): void
    {
        $dt = new \DateTimeImmutable('2024-01-01T15:00:00.456000', new \DateTimeZone('+05:00'));

        self::assertSame('2024-01-01T10:00:00.456Z', $this->normalizer->normalize($dt));
    }

    public function testNormalizesNonUtcDateTimeToUtc(): void
    {
        $dt = new \DateTime('2024-01-01T15:00:00', new \DateTimeZone('+05:00'));

        self::assertSame('2024-01-01T10:00:00.000Z', $this->normalizer->normalize($dt));
    }

    // -------------------------------------------------------------------------
    // supportsDenormalization
    // -------------------------------------------------------------------------

    public function testSupportsDenormalizationForDateTimeImmutableStringWithContextKey(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(
            '2024-01-01T12:00:00Z',
            \DateTimeImmutable::class,
            context: $this->optInContext,
        ));
    }

    public function testSupportsDenormalizationForDateTimeStringWithContextKey(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(
            '2024-01-01T12:00:00Z',
            \DateTime::class,
            context: $this->optInContext,
        ));
    }

    public function testSupportsDenormalizationForDateTimeInterfaceStringWithContextKey(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(
            '2024-01-01T12:00:00Z',
            \DateTimeInterface::class,
            context: $this->optInContext,
        ));
    }

    public function testSupportsDenormalizationReturnsFalseWithoutContextKey(): void
    {
        self::assertFalse($this->normalizer->supportsDenormalization(
            '2024-01-01T12:00:00Z',
            \DateTimeImmutable::class,
        ));
    }

    public function testSupportsDenormalizationReturnsFalseForWrongType(): void
    {
        self::assertFalse($this->normalizer->supportsDenormalization(
            '2024-01-01T12:00:00Z',
            \stdClass::class,
            context: $this->optInContext,
        ));
    }

    public function testSupportsDenormalizationReturnsFalseForNonString(): void
    {
        self::assertFalse($this->normalizer->supportsDenormalization(
            12345,
            \DateTimeImmutable::class,
            context: $this->optInContext,
        ));
    }

    // -------------------------------------------------------------------------
    // denormalize
    // -------------------------------------------------------------------------

    public function testDenormalizesIsoStringWithZSuffix(): void
    {
        $result = $this->normalizer->denormalize('2024-01-01T12:00:00Z', \DateTimeImmutable::class);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-01T12:00:00Z', $result->format('Y-m-d\TH:i:s\Z'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testDenormalizesIsoStringWithPositiveOffsetToUtc(): void
    {
        // 15:00:00+05:30 → 09:30:00 UTC
        $result = $this->normalizer->denormalize('2024-01-01T15:00:00+05:30', \DateTimeImmutable::class);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-01T09:30:00Z', $result->format('Y-m-d\TH:i:s\Z'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testDenormalizesIsoStringWithMilliseconds(): void
    {
        $result = $this->normalizer->denormalize('2024-01-01T12:00:00.123Z', \DateTimeImmutable::class);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('123', $result->format('v'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testDenormalizesIsoStringWithMicroseconds(): void
    {
        $result = $this->normalizer->denormalize('2024-01-01T12:00:00.123456Z', \DateTimeImmutable::class);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('123456', $result->format('u'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testDenormalizesIsoStringToDateTime(): void
    {
        $result = $this->normalizer->denormalize('2024-01-01T12:00:00.123456Z', \DateTime::class);

        self::assertInstanceOf(\DateTime::class, $result);
        self::assertSame('123456', $result->format('u'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testDenormalizesIsoStringToDateTimeInterface(): void
    {
        $result = $this->normalizer->denormalize('2024-01-01T12:00:00Z', \DateTimeInterface::class);

        self::assertInstanceOf(\DateTimeInterface::class, $result);
        self::assertSame('2024-01-01T12:00:00Z', $result->format('Y-m-d\TH:i:s\Z'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testDenormalizationThrowsForStringWithoutTimezone(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->normalizer->denormalize('2024-01-01T12:00:00', \DateTimeImmutable::class);
    }

    public function testDenormalizationThrowsForInvalidString(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->normalizer->denormalize('not-a-datetime', \DateTimeImmutable::class);
    }
}
