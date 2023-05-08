<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\HttpFoundation\Response;

class LocalData
{
    public const SEPARATOR = ',';

    public const INCLUDE_PARAMETER_NAME = 'includeLocal';
    public const QUERY_PARAMETER_NAME = 'queryLocal';

    public const LOCAL_DATA_ATTRIBUTES = 'local_data_attributes';
    public const LOCAL_QUERY_ATTRIBUTES = 'local_query_attributes';

    public static function getIncludeParameter(array $options): ?string
    {
        return $options[self::INCLUDE_PARAMETER_NAME] ?? null;
    }

    public static function getQueryParameter(array $options): ?string
    {
        return $options[self::QUERY_PARAMETER_NAME] ?? null;
    }

    /**
     * @throws ApiError if the given local data parameters are invalid
     */
    public static function addOptions(array &$targetOptions, array $sourceOptions): array
    {
        if ($includeLocalParameter = self::getIncludeParameter($sourceOptions)) {
            $targetOptions[self::LOCAL_DATA_ATTRIBUTES] = self::parseIncludeParameter($includeLocalParameter);
        }

        if ($queryLocalParameter = self::getQueryParameter($sourceOptions)) {
            $targetOptions[self::LOCAL_QUERY_ATTRIBUTES] = self::parseQueryParameter($queryLocalParameter);
        }

        return $targetOptions;
    }

    public static function removeOptions(array &$options)
    {
        unset($options[self::LOCAL_DATA_ATTRIBUTES]);
        unset($options[self::LOCAL_QUERY_ATTRIBUTES]);
    }

    public static function getLocalDataAttributes(array $options): array
    {
        return $options[self::LOCAL_DATA_ATTRIBUTES] ?? [];
    }

    public static function getLocalQueryAttributes(array $options): array
    {
        return $options[self::LOCAL_QUERY_ATTRIBUTES] ?? [];
    }

    /**
     * Adds the 'include Local Data' parameter to the set of options in order to request the local data attributes with the given names.
     *
     * @param array $targetOptions  the set of options to add the 'include Local Data' parameter to
     * @param array $attributeNames the names of the local data attributes to request
     *
     * @return array the resultant $targetOptions
     */
    public static function requestLocalDataAttributes(array &$targetOptions, array $attributeNames): array
    {
        $targetOptions[self::LOCAL_DATA_ATTRIBUTES] = $attributeNames;

        return $targetOptions;
    }

    private static function parseIncludeParameter(string $includeParameter): array
    {
        $localDataAttributes = explode(self::SEPARATOR, $includeParameter);

        return array_unique($localDataAttributes);
    }

    /**
     * @throws ApiError
     */
    private static function parseQueryParameter(string $queryParameter): array
    {
        $localQueryAttributes = [];

        foreach (explode(self::SEPARATOR, $queryParameter) as $queryAttributeAssignment) {
            $queryAttributeAssignment = trim($queryAttributeAssignment);
            if ($queryAttributeAssignment !== '') {
                $parameterKey = null;
                $parameterValue = null;

                if (!self::parseQueryParameterAssignment($queryAttributeAssignment, $parameterKey, $parameterValue)) {
                    throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf("'%s' parameter has invalid format: '%s' (Example: 'param1:val1,attr1:val2')", LocalData::QUERY_PARAMETER_NAME, $queryAttributeAssignment));
                }

                if (isset($localQueryAttributes[$parameterKey])) {
                    throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('multiple definition of local query attribute \'%s\'', $parameterKey));
                }

                $localQueryAttributes[$parameterKey] = urldecode((string) $parameterValue);
            }
        }

        return $localQueryAttributes;
    }

    /**
     * Parses a local data attribute of the form 'UniqueEntityName.attributeName'.
     * NOTE: Due to possible performance impact, there is currently no regex check for valid entity and attribute names (i.e. PHP type/variable names).
     *
     * @retrun true if $localDataAttribute complies with the local attribute format, false otherwise
     */
    private static function parseQueryParameterAssignment(string $parameterAssignment, ?string &$parameter, ?string &$value): bool
    {
        $parameter = null;
        $value = null;

        $parts = explode(':', $parameterAssignment);

        $parameter = $parts[0] ?? null;
        $value = $parts[1] ?? null;
        $numParts = count($parts);

        return !Tools::isNullOrEmpty($parameter) && ($numParts === 1 || ($numParts === 2 && !Tools::isNullOrEmpty($value)));
    }
}
