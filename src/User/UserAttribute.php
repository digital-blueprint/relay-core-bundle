<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

class UserAttribute
{
    private const USER_ATTRIBUTE_DELIMITER = '.';

    public static function getAttributeName(string $namespace, string $resource, string $action): string
    {
        return $namespace.self::USER_ATTRIBUTE_DELIMITER.$resource.self::USER_ATTRIBUTE_DELIMITER.$action;
    }
}
