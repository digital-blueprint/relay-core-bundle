<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Serializer;

use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer/denormalizer for {@see \DateTimeInterface} that enforces UTC and strict ISO 8601 with timezone.
 *
 * Opt in per property:
 *   #[Context([DateTimeUtcNormalizer::CONTEXT_KEY => true])]
 *   public \DateTimeInterface $createdAt;
 *
 * Normalization output format: 2024-01-01T12:00:00.000Z  (always includes milliseconds)
 *
 * Denormalization accepts ISO 8601 strings with an explicit timezone (Z or +HH:MM).
 * Both milliseconds (3 digits) and microseconds (6 digits) are accepted.
 * The parsed value is always converted to UTC.
 */
class DateTimeUtcNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const CONTEXT_KEY = 'relay_core_datetime_utc';

    private const FORMAT_MILLISECONDS = 'Y-m-d\TH:i:s.v\Z';

    private const DENORM_FORMATS = [
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:sP',
    ];

    private static \DateTimeZone $utc;

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        assert($data instanceof \DateTimeInterface);

        $utc = \DateTimeImmutable::createFromInterface($data)->setTimezone(self::utc());

        return $utc->format(self::FORMAT_MILLISECONDS);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof \DateTimeInterface && ($context[self::CONTEXT_KEY] ?? false) === true;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): \DateTimeInterface
    {
        assert(is_string($data));

        foreach (self::DENORM_FORMATS as $fmt) {
            $dt = $this->createFromFormat($fmt, $data, $type);
            if ($dt !== false) {
                if ($dt instanceof \DateTimeImmutable) {
                    return $dt->setTimezone(self::utc());
                }

                if ($dt instanceof \DateTime) {
                    $dt->setTimezone(self::utc());

                    return $dt;
                }

                throw new \RuntimeException('Unsupported DateTimeInterface implementation.');
            }
        }

        throw NotNormalizableValueException::createForUnexpectedDataType(
            sprintf(
                'The value "%s" is not a valid ISO 8601 datetime string with timezone. '
                .'Expected format: Y-m-d\TH:i:sP or Y-m-d\TH:i:s.uP (e.g. "2024-01-01T12:00:00Z" or "2024-01-01T12:00:00+05:30").',
                $data,
            ),
            $data,
            ['string'],
            $context['deserialization_path'] ?? null,
            true,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return in_array($type, [\DateTimeInterface::class, \DateTimeImmutable::class, \DateTime::class], true)
            && is_string($data)
            && ($context[self::CONTEXT_KEY] ?? false) === true;
    }

    public function getSupportedTypes(?string $format): array
    {
        // We can't cache on the type, since the type is also handled by Symfony when no
        // CONTEXT_KEY is set.
        return [
            \DateTimeInterface::class => false,
            \DateTimeImmutable::class => false,
            \DateTime::class => false,
        ];
    }

    private static function utc(): \DateTimeZone
    {
        return self::$utc ??= new \DateTimeZone('UTC');
    }

    private function createFromFormat(string $format, string $value, string $type): \DateTimeInterface|false
    {
        if ($type === \DateTime::class) {
            return \DateTime::createFromFormat($format, $value);
        }

        return \DateTimeImmutable::createFromFormat($format, $value);
    }
}
