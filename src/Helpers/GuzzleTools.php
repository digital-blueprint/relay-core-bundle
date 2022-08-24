<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Helpers;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

/**
 * @deprecated copy this code instead
 */
class GuzzleTools
{
    /**
     * @deprecated copy this code instead
     */
    public static function createLoggerMiddleware(LoggerInterface $logger): callable
    {
        return Middleware::log(
            $logger,
            new MessageFormatter('[{method}] {uri}: CODE={code}, ERROR={error}, CACHE={res_header_X-Kevinrob-Cache}')
        );
    }
}
