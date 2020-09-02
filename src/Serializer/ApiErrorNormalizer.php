<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Serializer;

use DBP\API\CoreBundle\Exception\ApiError;
use Symfony\Component\Debug\Exception\FlattenException as LegacyFlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/**
 * A workaround to API-Platform by default stripping error messages from 500-600 HttpException
 * instances. This gets called first, calls the real normalizer internally and adds back the
 * error message based on the format.
 *
 * To avoid leaking internals for 500+ errors we only do this for a special APIError class
 * which is a subclass of HttpException.
 */
class ApiErrorNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ALREADY_CALLED_'.ApiErrorNormalizer::class;

    private const SUPPORTED_CLASS = APIError::class;

    public function normalize($object, $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $normalized = $this->normalizer->normalize($object, $format, $context);

        if ($object->getClass() === self::SUPPORTED_CLASS) {
            $message = $object->getMessage();
            if ($format === 'jsonld') {
                if ($message !== '') {
                    $normalized['hydra:description'] = $message;
                }
            } elseif ($format === 'jsonproblem') {
                if ($message !== '') {
                    $normalized['detail'] = $message;
                }
            }
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        // Make sure we're not called twice
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return
            ($format === 'jsonld' || $format === 'jsonproblem') &&
            ($data instanceof FlattenException || $data instanceof LegacyFlattenException);
    }
}
