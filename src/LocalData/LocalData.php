<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Options;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class LocalData
{
    private const SEPARATOR = ',';

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        return LocalDataAccessChecker::getConfigNodeDefinition();
    }

    /**
     * @throws ApiError if the given local data parameters are invalid
     */
    public static function getLocalDataAttributesFromQueryParameter(string $queryParameter): array
    {
        return self::parseIncludeParameter($queryParameter);
    }

    public static function getQueryParameterFromLocalDataAttributes(array $attributes): string
    {
        return implode(self::SEPARATOR, $attributes);
    }

    /**
     * @deprecated Use Dbp\Relay\CoreBundle\HttpOperations\Options::requestLocalDataAttributes instead
     * Adds the 'include Local Data' parameter to the set of options in order to request the local data attributes with the given names
     *
     * @param array $targetOptions  the set of options to add the 'include Local Data' parameter to
     * @param array $attributeNames the names of the local data attributes to request
     *
     * @return array the resultant $targetOptions
     */
    public static function requestLocalDataAttributes(array &$targetOptions, array $attributeNames): array
    {
        return Options::requestLocalDataAttributes($targetOptions, $attributeNames);
    }

    private static function parseIncludeParameter(string $includeParameter): array
    {
        $localDataAttributes = explode(self::SEPARATOR, $includeParameter);

        return array_unique(
            array_map(function ($localDataAttribute) {
                return trim($localDataAttribute);
            }, $localDataAttributes)
        );
    }
}
