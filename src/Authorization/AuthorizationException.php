<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationException extends \RuntimeException
{
    public const PRIVILEGE_UNDEFINED = 1;
    public const USER_NOT_AVAILABLE = 2;
}
