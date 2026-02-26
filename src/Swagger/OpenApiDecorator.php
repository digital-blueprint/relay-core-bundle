<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Swagger;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;

final class OpenApiDecorator implements OpenApiFactoryInterface
{
    private $decorated;
    private $pathsToHide;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->pathsToHide = [];
    }

    public function setPathsToHide(array $pathsToHide)
    {
        $this->pathsToHide = $pathsToHide;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Hide methods that are marked hidden
        $pathsToHide = $this->pathsToHide;
        $paths = $openApi->getPaths();
        $newPaths = new Paths();
        foreach ($paths->getPaths() as $path => $pathItem) {
            $pathItem = $paths->getPath($path);
            if ($pathItem->getGet() !== null && in_array([$path, 'GET'], $pathsToHide, true)) {
                $pathItem = $pathItem->withGet(null);
            }
            if ($pathItem->getPost() !== null && in_array([$path, 'POST'], $pathsToHide, true)) {
                $pathItem = $pathItem->withPost(null);
            }
            if ($pathItem->getDelete() !== null && in_array([$path, 'DELETE'], $pathsToHide, true)) {
                $pathItem = $pathItem->withDelete(null);
            }
            if ($pathItem->getPut() !== null && in_array([$path, 'PUT'], $pathsToHide, true)) {
                $pathItem = $pathItem->withPut(null);
            }
            if ($pathItem->getPatch() !== null && in_array([$path, 'PATCH'], $pathsToHide, true)) {
                $pathItem = $pathItem->withPatch(null);
            }
            $newPaths->addPath($path, $pathItem);
        }
        $paths = $newPaths;

        // Add an "Accept-Language" header to each method
        $langParam = new Parameter(
            name: 'Accept-Language',
            in: 'header',
            description: 'Preferred language for the response content',
            required: false,
            schema: [
                'type' => 'string',
                'enum' => ['de', 'en'],
                'default' => 'en',
            ],
        );
        $newPaths = new Paths();
        foreach ($paths->getPaths() as $path => $pathItem) {
            $pathItem = $paths->getPath($path);
            $pathItem = $pathItem->withParameters(array_merge($pathItem->getParameters() ?? [], [$langParam]));
            $newPaths->addPath($path, $pathItem);
        }

        return $openApi->withPaths($newPaths);
    }
}
