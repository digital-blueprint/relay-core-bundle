<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\LocalData\AbstractLocalDataPostEventSubscriber;

class TestEntityLocalDataEventSubscriber extends AbstractLocalDataPostEventSubscriber
{
    public static function getSubscribedEventNames(): array
    {
        return [
            TestEntityPostEvent::class,
            TestEntityPreEvent::class,
            ];
    }
}
