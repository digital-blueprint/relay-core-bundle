<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

class LocalData
{
    public const INCLUDE_PARAMETER_NAME = 'includeLocal';
    public const QUERY_PARAMETER_NAME = 'queryLocal';

    public static function addOptions(array &$targetOptions, array $sourceOptions)
    {
        if ($includeLocalParameter = self::getIncludeParameter($sourceOptions)) {
            $targetOptions[self::INCLUDE_PARAMETER_NAME] = $includeLocalParameter;
        }

        if ($queryLocalParameter = self::getQueryParameter($sourceOptions)) {
            $targetOptions[self::QUERY_PARAMETER_NAME] = $queryLocalParameter;
        }
    }

    public static function removeOptions(array &$options)
    {
        unset($options[self::INCLUDE_PARAMETER_NAME]);
        unset($options[self::QUERY_PARAMETER_NAME]);
    }

    public static function getIncludeParameter(array $options): ?string
    {
        return $options[self::INCLUDE_PARAMETER_NAME] ?? null;
    }

    public static function getQueryParameter(array $options): ?string
    {
        return $options[self::QUERY_PARAMETER_NAME] ?? null;
    }

    /**
     * @deprecated Use addIncludeParameter
     */
    public static function toIncludeLocalParameterValue(array $attributeNames): string
    {
        return self::toIncludeParameterValue($attributeNames);
    }

    public static function addIncludeParameter(array &$targetOptions, array $attributeNames): void
    {
        $targetOptions[self::INCLUDE_PARAMETER_NAME] = self::toIncludeParameterValue($attributeNames);
    }

    private static function toIncludeParameterValue(array $attributeNames): string
    {
        return implode(LocalDataEventDispatcher::SEPARATOR, $attributeNames);
    }
}
