<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;

class Options
{
    public const LANGUAGE = 'lang';
    public const FILTER = 'filter';
    public const LOCAL_DATA_ATTRIBUTES = 'local_data_attributes';

    public static function getLanguage(array $options): ?string
    {
        return $options[self::LANGUAGE] ?? null;
    }

    public static function setLanguage(array &$targetOptions, string $language): array
    {
        $targetOptions[self::LANGUAGE] = $language;

        return $targetOptions;
    }

    public static function getFilter(array $options): ?Filter
    {
        return $options[self::FILTER] ?? null;
    }

    public static function addFilter(array &$targetOptions, Filter $filter): array
    {
        if ($existingFilter = $targetOptions[Options::FILTER] ?? null) {
            $existingFilter->combineWith($filter);
        } else {
            $targetOptions[Options::FILTER] = $filter;
        }

        return $targetOptions;
    }

    public static function getLocalDataAttributes(array $options): array
    {
        return $options[self::LOCAL_DATA_ATTRIBUTES] ?? [];
    }

    public static function setLocalDataAttributes(array &$targetOptions, array $localDataAttributes): array
    {
        $targetOptions[self::LOCAL_DATA_ATTRIBUTES] = $localDataAttributes;

        return $targetOptions;
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
        $targetOptions[self::LOCAL_DATA_ATTRIBUTES] = LocalData::getLocalDataAttributesFromAttributeNames($attributeNames);

        return $targetOptions;
    }
}
