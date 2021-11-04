<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Queue;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RestartCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('dbp:relay:queue:restart');
        $commandTester = new CommandTester($command);
        $res = $commandTester->execute([]);
        $this->assertSame(0, $res);
    }
}
