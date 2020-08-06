<?php

namespace DBP\API\CoreBundle\Swagger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class SwaggerDecorator
 * see: https://api-platform.com/docs/core/swagger/#overriding-the-openapi-specification.
 */
final class SwaggerDecorator implements NormalizerInterface
{
    private $decorated;
    private $container;

    public function __construct(NormalizerInterface $decorated, ContainerInterface $container)
    {
        $this->decorated = $decorated;
        $this->container = $container;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        $pathsToHide = [];
        if ($this->container->hasParameter('dbp_api.paths_to_hide')) {
            $pathsToHide = array_merge($pathsToHide, $this->container->getParameter('dbp_api.paths_to_hide'));
        }

        foreach ($pathsToHide as $path) {
            unset($docs['paths'][$path]);
        }

        return $docs;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
