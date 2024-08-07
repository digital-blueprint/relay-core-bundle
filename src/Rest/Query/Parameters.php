<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query;

class Parameters
{
    public const FILTER = 'filter';
    public const PREPARED_FILTER = 'preparedFilter';
    public const INCLUDE_LOCAL = 'includeLocal';
    public const SORTING = 'sort';

    public static function getFilter(array $parameters): mixed
    {
        return $parameters[self::FILTER] ?? null;
    }

    public static function getPreparedFilter(array $parameters): ?string
    {
        return $parameters[self::PREPARED_FILTER] ?? null;
    }

    public static function getSorting(array $parameters)
    {
        return $parameters[self::SORTING] ?? null;
    }

    public static function getIncludeLocal(array $parameters): ?string
    {
        return $parameters[self::INCLUDE_LOCAL] ?? null;
    }

    public static function setIncludeLocal(array &$targetParameters, string $parameterValue): array
    {
        $targetParameters[self::INCLUDE_LOCAL] = $parameterValue;

        return $targetParameters;
    }

    /**
     * @param string $queryString   The substring after the question mark character of a URL
     * @param string $parameterName The (base) name of the query parameter, i.e. the parameter key before possible square brackets
     *
     * @return array|string array for, e.g.: 'foo[bar] = baz'; string for, e.g.: 'foo = bar'
     */
    public static function getQueryParametersFromQueryString(string $queryString, string $parameterName): mixed
    {
        $queryParameters = [];
        parse_str($queryString, $queryParameters);

        return $queryParameters[$parameterName] ?? [];
    }
}
