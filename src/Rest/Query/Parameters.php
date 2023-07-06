<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query;

class Parameters
{
    public const FILTER = 'filter';
    public const PREPARED_FILTER = 'preparedFilter';
    public const INCLUDE_LOCAL = 'includeLocal';

    public static function getFilter(array $parameters)
    {
        return $parameters[self::FILTER] ?? null;
    }

    public static function getPreparedFilter(array $parameters): ?string
    {
        return $parameters[self::PREPARED_FILTER] ?? null;
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
}
