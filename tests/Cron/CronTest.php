<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Cron;

use Dbp\Relay\CoreBundle\Cron\CronCommand;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    public function testCronisDue()
    {
        $isDue = CronCommand::isDue(new \DateTimeImmutable('2021-09-07T09:36:26Z'), new \DateTimeImmutable('2021-09-07T09:36:26Z'), '* * * * *');
        $this->assertFalse($isDue);
        $isDue = CronCommand::isDue(new \DateTimeImmutable('2021-09-07T09:35:59Z'), new \DateTimeImmutable('2021-09-07T09:36:00Z'), '* * * * *');
        $this->assertTrue($isDue);
        $isDue = CronCommand::isDue(null, new \DateTimeImmutable('2021-09-07T09:36:00Z'), '0 0 1 1 *');
        $this->assertTrue($isDue);
        $isDue = CronCommand::isDue(null, new \DateTimeImmutable('2021-09-07T09:36:00Z'), '* * * * *');
        $this->assertTrue($isDue);
    }
}