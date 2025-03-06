<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

class Parameters
{
    public const FILTER = 'filter';
    public const PREPARED_FILTER = 'preparedFilter';
    public const INCLUDE_LOCAL = 'includeLocal';
    public const SORT = 'sort';

    public static function getFilter(array $parameters): mixed
    {
        return $parameters[self::FILTER] ?? null;
    }

    public static function getPreparedFilter(array $parameters): ?string
    {
        return $parameters[self::PREPARED_FILTER] ?? null;
    }

    public static function getSort(array $parameters)
    {
        return $parameters[self::SORT] ?? null;
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

    /**
     * @throws ApiError
     */
    public static function getBool(array $parameters, string $parameterName, bool $defaultValue = false,
        bool $throwOnSyntaxError = true, bool $throwIfMissing = false): bool
    {
        if (($value = $parameters[$parameterName] ?? null) !== null) {
            $returnValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($returnValue !== null) {
                return $returnValue;
            } elseif ($throwOnSyntaxError) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Invalid value for boolean parameter');
            }
        } elseif ($throwIfMissing) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('Parameter \'%s\' is required', $parameterName));
        }

        return $defaultValue;
    }
}
