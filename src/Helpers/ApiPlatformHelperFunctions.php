<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Helpers;

use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceMetadataFactory;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Doctrine\Common\Annotations\AnnotationReader;

class ApiPlatformHelperFunctions
{
    /**
     * Returns the 'shortName' attribute of the ApiResource annotation of the entity with the given class name.
     *
     * @throws ApiError if the ApiResource annotation of $resourceClass is not found or doesn't have a non-empty 'shortName' attribute
     */
    public static function getShortNameForResource(string $resourceClass): string
    {
        $resourceMetadataFactory = new AnnotationResourceMetadataFactory(new AnnotationReader());
        try {
            $resourceMetadata = $resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $exc) {
            throw new ApiError(500, $exc->getMessage());
        }

        $uniqueName = $resourceMetadata->getShortName() ?? '';
        if (Tools::isNullOrEmpty($uniqueName)) {
            throw new ApiError(500, sprintf("'shortName' attribute missing in ApiResource annotation of resource class '%s'", $resourceClass));
        } elseif (str_contains($uniqueName, '.') || str_contains($uniqueName, ',')) {
            throw new ApiError(500, sprintf("'shortName' attribute of resource class '%s' must not contain '.' or ',' characters: '%s'", $resourceClass, $uniqueName));
        }

        return $uniqueName;
    }
}
