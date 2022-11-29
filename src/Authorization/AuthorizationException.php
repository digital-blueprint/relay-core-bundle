<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationException extends \RuntimeException
{
    public const PRIVILEGE_UNDEFINED = 2;
    public const ATTRIBUTE_UNDEFINED = 3;
    public const INFINITE_LOOP_DETECTED = 4;
}
