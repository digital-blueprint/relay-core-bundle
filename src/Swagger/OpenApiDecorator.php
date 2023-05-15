<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Swagger;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
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
        $pathsToHide = $this->pathsToHide;
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
