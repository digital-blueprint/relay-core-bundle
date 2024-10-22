<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationException extends \RuntimeException
{
    /* @deprecated Since v0.1.188, use ROLE_UNDEFINED or RESOURCE_PERMISSION_UNDEFINED or  instead. */
    public const POLICY_UNDEFINED = 1;
    public const RESOURCE_PERMISSION_UNDEFINED = 1;
    public const ATTRIBUTE_UNDEFINED = 2;
    public const INFINITE_EXPRESSION_LOOP_DETECTED = 3;
    public const ROLE_UNDEFINED = 4;
}
