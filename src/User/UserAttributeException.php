<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

class UserAttributeException extends \Exception
{
    public const USER_ATTRIBUTE_UNDEFINED = 1;
    public const INFINITE_EVENT_LOOP_DETECTED = 2;
}
