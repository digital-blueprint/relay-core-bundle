<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Cron;

use Dbp\Relay\CoreBundle\Cron\CronCommand;
use Dbp\Relay\CoreBundle\Cron\CronListCommand;
use Dbp\Relay\CoreBundle\Cron\CronManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CronTest extends TestCase
{
    public function testCronIsDue()
    {
        $isDue = CronManager::isDue(new CronJob('* * * * *'), new \DateTimeImmutable('2021-09-07T09:36:26Z'), new \DateTimeImmutable('2021-09-07T09:36:26Z'));
        $this->assertFalse($isDue);
        $isDue = CronManager::isDue(new CronJob('* * * * *'), new \DateTimeImmutable('2021-09-07T09:35:59Z'), new \DateTimeImmutable('2021-09-07T09:36:00Z'));
        $this->assertTrue($isDue);
        $isDue = CronManager::isDue(new CronJob('0 0 1 1 *'), null, new \DateTimeImmutable('2021-09-07T09:36:00Z'));
        $this->assertFalse($isDue);
        $isDue = CronManager::isDue(new CronJob('* * * * *'), null, new \DateTimeImmutable('2021-09-07T09:36:00Z'));
        $this->assertFalse($isDue);
    }

    public function testExecutionDate()
    {
        $man = new CronManager();
        $man->setCache(new ArrayAdapter());
        $this->assertNull($man->getLastExecutionDate());
        $date = (new \DateTimeImmutable())->setTimestamp(42);
        $man->setLastExecutionDate($date);
        $this->assertSame(42, $man->getLastExecutionDate()->getTimestamp());
    }

    public function testGetNextDate()
    {
        $current = (new \DateTimeImmutable())->setTimestamp(1676383110);
        $next = CronManager::getNextDate(new CronJob('*/5 * * * *'), $current);
        $this->assertSame(1676383200, $next->getTimestamp());

        $current = new \DateTimeImmutable('2024-12-17T14:10:22+00:00', new \DateTimeZone('UTC'));
        $next = CronManager::getNextDate(new CronJob('* * * * *'), $current);
        $this->assertSame('2024-12-17T14:11:00+00:00', $next->format(\DateTime::ATOM));
    }

    public function testManager()
    {
        $last = (new \DateTimeImmutable())->setTimestamp(1676383110);
        $current = $last->setTimestamp($last->getTimestamp() + 30);
        $man = new CronManager();
        $man->setCache(new ArrayAdapter());
        $man->setLastExecutionDate($last);
        $job = new CronJob('* * * * *');

        // add a job
        $man->addJob($job);
        $this->assertSame($job, $man->getJobs()[0]);

        // run right away
        $man->runDueJobs(false, $last);
        $this->assertFalse($job->ran);

        // now a bit later, the job gets run
        $man->runDueJobs(false, $current);
        $this->assertTrue($job->ran);

        // now right away again, but force
        $job->ran = false;
        $man->runDueJobs(false, $current);
        $this->assertFalse($job->ran);
        $man->runDueJobs(true, $current);
        $this->assertTrue($job->ran);
    }

    public function testCommands()
    {
        $man = new CronManager();
        $job = new CronJob('* * * * *');
        $man->addJob($job);

        // cron command
        new CronCommand($man);

        // list command
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $cmd = new CronListCommand($man);
        $this->assertSame(0, $cmd->run($input, $output));
    }
}
