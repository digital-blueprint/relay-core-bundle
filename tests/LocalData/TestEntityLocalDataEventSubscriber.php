<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\LocalData\AbstractLocalDataPostEventSubscriber;
use Dbp\Relay\CoreBundle\LocalData\LocalDataPreEvent;

class TestEntityLocalDataEventSubscriber extends AbstractLocalDataPostEventSubscriber
{
    public static function getSubscribedEventNames(): array
    {
        return [
            TestEntityPostEvent::class,
            TestEntityPreEvent::class,
            ];
    }

    protected function onPreEvent(LocalDataPreEvent $preEvent, array $mappedQueryParameters)
    {
        $preEvent->setOptions($mappedQueryParameters);
    }
}
