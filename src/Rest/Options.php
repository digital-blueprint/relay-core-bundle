<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;

class Options
{
    public const LANGUAGE = 'lang';
    public const FILTER = 'filter';
    public const SORT = 'sort';
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

    /**
     * Sets the filter option in the given target options.
     */
    public static function setFilter(array &$targetOptions, Filter $filter): array
    {
        $targetOptions[Options::FILTER] = $filter;

        return $targetOptions;
    }

    /**
     * Sets the filter option in the given target options. If the filter option is already set, the existing filter and
     * the given filter combined with a logical AND operator.
     */
    public static function addFilter(array &$targetOptions, Filter $filter): array
    {
        if ($existingFilter = $targetOptions[Options::FILTER] ?? null) {
            $existingFilter->combineWith($filter);
        } else {
            $targetOptions[Options::FILTER] = $filter;
        }

        return $targetOptions;
    }

    /**
     * Removes the filter option from the given target options.
     */
    public static function removeFilter(array &$targetOptions): array
    {
        unset($targetOptions[self::FILTER]);

        return $targetOptions;
    }

    public static function getSort(array $options): ?Sort
    {
        return $options[self::SORT] ?? null;
    }

    /**
     * Sets the sort option in the given target options.
     */
    public static function setSort(array &$targetOptions, Sort $sort): array
    {
        $targetOptions[self::SORT] = $sort;

        return $targetOptions;
    }

    /**
     * Removes the sort option from the given target options.
     */
    public static function removeSort(array &$targetOptions): array
    {
        unset($targetOptions[self::SORT]);

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
     * Request the given list of local data attributes by adding the 'local data attributes' option
     * to the given set of options.
     *
     * @param array $targetOptions       a reference to the set of options to add the 'local data attributes' option to
     * @param array $localDataAttributes the list of names of local data attributes to request
     *
     * @return array the resultant $targetOptions
     */
    public static function requestLocalDataAttributes(array &$targetOptions, array $localDataAttributes): array
    {
        return self::setLocalDataAttributes($targetOptions, $localDataAttributes);
    }
}
