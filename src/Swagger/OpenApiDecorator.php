<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Swagger;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Paths;
use ApiPlatform\Core\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * see: https://api-platform.com/docs/core/openapi/.
 */
final class OpenApiDecorator implements OpenApiFactoryInterface
{
    private $decorated;
    private $container;

    public function __construct(OpenApiFactoryInterface $decorated, ContainerInterface $container)
    {
        $this->decorated = $decorated;
        $this->container = $container;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $pathsToHide = [];
        if ($this->container->hasParameter('dbp_api.paths_to_hide')) {
            $pathsToHide = array_merge($pathsToHide, $this->container->getParameter('dbp_api.paths_to_hide'));
        }

        $paths = $openApi->getPaths();
        $newPaths = new Paths();
        foreach ($paths->getPaths() as $path => $pathItem) {
            $pathItem = $paths->getPath($path);
            if ($pathItem->getGet() !== null && in_array($path, $pathsToHide, true)) {
                $pathItem = $pathItem->withGet(null);
            }
            $newPaths->addPath($path, $pathItem);
        }

        return $openApi->withPaths($newPaths);
    }
}
