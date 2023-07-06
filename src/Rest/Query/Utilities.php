<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query;

class Utilities
{
    /**
     * @param string $queryString   The substring after the question mark character of a URL
     * @param string $parameterName The (base) name of the query parameter, i.e. the parameter key before possible square brackets
     */
    public static function getQueryParametersFromQueryString(string $queryString, string $parameterName): array
    {
        $queryParameters = [];
        parse_str($queryString, $queryParameters);

        return $queryParameters[$parameterName] ?? [];
    }
}
