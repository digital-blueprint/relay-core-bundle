<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationException extends \RuntimeException
{
    public const POLICY_UNDEFINED = 1;
    public const ATTRIBUTE_UNDEFINED = 2;
    public const INFINITE_EXRPESSION_LOOP_DETECTED = 3;
    public const INFINITE_EVENT_LOOP_DETECTED = 4;
}
