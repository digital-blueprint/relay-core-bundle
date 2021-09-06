<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Cron\CronEvent;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    public function testCronEvent()
    {
        $event = new CronEvent(new \DateTimeImmutable('2021-09-07T09:36:26Z'), new \DateTimeImmutable('2021-09-07T09:36:26Z'));
        $this->assertFalse($event->isDue('foo', '* * * * *'));

        $event = new CronEvent(new \DateTimeImmutable('2021-09-07T09:35:59Z'), new \DateTimeImmutable('2021-09-07T09:36:00Z'));
        $this->assertTrue($event->isDue('foo', '* * * * *'));

        $event = new CronEvent(null, new \DateTimeImmutable('2021-09-07T09:36:00Z'));
        $this->assertTrue($event->isDue('foo', '0 0 1 1 *'));
        $this->assertTrue($event->isDue('foo', '* * * * *'));
    }
}
