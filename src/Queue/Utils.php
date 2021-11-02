<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

class Utils
{
    public const QUEUE_TRANSPORT_NAME = 'async';

    // These just should be good defaults, feel free to adjust
    public const DEFAULT_TIME_LIMIT = 3600;
    public const DEFAULT_TASK_LIMIT = 10;
}
