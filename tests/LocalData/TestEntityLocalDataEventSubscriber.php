<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\LocalData\AbstractLocalDataEventSubscriber;

class TestEntityLocalDataEventSubscriber extends AbstractLocalDataEventSubscriber
{
    public static function getSubscribedEventNames(): array
    {
        return [
            TestEntityPostEvent::class,
            TestEntityPreEvent::class,
            ];
    }
}
