<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

class LocalData
{
    public const INCLUDE_PARAMETER_NAME = 'includeLocal';

    public static function getIncludeParameter(array $filters): string
    {
        return $filters[self::INCLUDE_PARAMETER_NAME] ?? '';
    }
}
