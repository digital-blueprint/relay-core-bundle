<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\LocalData\AbstractLocalDataPostEventSubscriber;

class TestEntityLocalDataPostEventSubscriber extends AbstractLocalDataPostEventSubscriber
{
    public static function getSubscribedEventName(): string
    {
        return TestEntityPostEvent::class;
    }
}
