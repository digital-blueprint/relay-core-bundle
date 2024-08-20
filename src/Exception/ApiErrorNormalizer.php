<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Exception;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A workaround to API-Platform by default stripping error messages from 500-600 HttpException
 * instances. This gets called first, calls the real normalizer internally and adds back the
 * error message based on the format.
 *
 * To avoid leaking internals for 500+ errors we only do this for a special APIError class
 * which is a subclass of HttpException.
 */
class ApiErrorNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ALREADY_CALLED_'.ApiErrorNormalizer::class;

    /**
     * @return array|\ArrayObject|bool|float|int|string|null
     *
     * @throws ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $normalized = $this->normalizer->normalize($object, $format, $context);

        if ($object->getClass() === ApiError::class) {
            $message = $object->getMessage();
            $message = json_decode($message, true, flags: JSON_THROW_ON_ERROR);
            $errorId = $message['errorId'];
            $errorDetails = $message['errorDetails'];
            $message = $message['message'];

            if ($format === 'jsonld') {
                if ($message !== null) {
                    $normalized['hydra:description'] = $message;
                }
                if ($errorId !== '') {
                    $normalized['relay:errorId'] = $errorId;
                }
                if ($errorDetails !== null) {
                    $normalized['relay:errorDetails'] = (object) $errorDetails;
                }
            } elseif ($format === 'jsonproblem') {
                if ($message !== null) {
                    $normalized['detail'] = $message;
                }
                if ($errorId !== '') {
                    $normalized['errorId'] = $errorId;
                }
                if ($errorDetails !== null) {
                    $normalized['errorDetails'] = (object) $errorDetails;
                }
            }
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        // Make sure we're not called twice
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return
            ($format === 'jsonld' || $format === 'jsonproblem')
            && ($data instanceof FlattenException);
    }

    public function getSupportedTypes(?string $format): array
    {
        if ($format === 'jsonld' || $format === 'jsonproblem') {
            return [
                FlattenException::class => false,
            ];
        }

        return [];
    }
}
