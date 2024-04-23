<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Queue;

use Dbp\Relay\CoreBundle\Queue\Utils;
use Dbp\Relay\CoreBundle\Queue\WorkCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends KernelTestCase
{
    public function testRestartExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('dbp:relay:core:queue:restart');
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute([]);
        $this->assertSame(0, $res);
    }

    public function testQueueTestExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('dbp:relay:core:queue:test');
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute([]);
        $this->assertSame(0, $res);
        $res = $commandTester->execute(['--delay' => '42']);
        $this->assertSame(0, $res);
    }

    public function testWorkExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('dbp:relay:core:queue:test');
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute(['--count' => (string) Utils::DEFAULT_TASK_LIMIT]);
        $this->assertSame(0, $res);

        $command = $application->find('dbp:relay:core:queue:work');
        assert($command instanceof WorkCommand);
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute(['worker-name' => 'workername']);
        $this->assertSame(0, $res);
    }

    public function testWorkExecuteFailed()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('dbp:relay:core:queue:test');
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute(['--count' => (string) Utils::DEFAULT_TASK_LIMIT, '--fail']);
        $this->assertSame(0, $res);

        $command = $application->find('dbp:relay:core:queue:work');
        assert($command instanceof WorkCommand);
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute(['worker-name' => 'workername']);
        $this->assertSame(0, $res);

        $command = $application->find('messenger:failed:show');
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute(['--transport' => 'async_failed']);
        $this->assertSame(0, $res);
    }
}
