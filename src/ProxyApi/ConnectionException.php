<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

class ConnectionException extends \Dbp\Relay\CoreBundle\Http\ConnectionException
{
    public const API_ERROR = 101;
}
