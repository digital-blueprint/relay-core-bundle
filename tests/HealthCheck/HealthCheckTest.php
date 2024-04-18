<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\HealthCheck;

use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Dbp\Relay\CoreBundle\HealthCheck\Checks\CacheCheck;
use Dbp\Relay\CoreBundle\HealthCheck\Checks\LockCheck;
use Dbp\Relay\CoreBundle\HealthCheck\Checks\QueueCheck;
use Dbp\Relay\CoreBundle\HealthCheck\Checks\SymfonyCheck;
use Dbp\Relay\CoreBundle\HealthCheck\HealthCheckCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class HealthCheckTest extends KernelTestCase
{
    public function testExecuteDummy(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('dbp:relay:core:check-health');
        assert($command instanceof HealthCheckCommand);
        $command->setChecks([new DummyCheck()]);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $this->assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Dummy Check', $output);
    }

    public function testCacheCheck(): void
    {
        $check = new CacheCheck(new ArrayAdapter());
        $this->assertSame('core.cache', $check->getName());
        $results = $check->check(new CheckOptions());
        $this->assertCount(1, $results);
        $this->assertSame(CheckResult::STATUS_SUCCESS, $results[0]->getStatus());
    }

    public function testLockCheck(): void
    {
        $check = new LockCheck(new LockFactory(new SemaphoreStore()));
        $this->assertSame('core.lock', $check->getName());
        $results = $check->check(new CheckOptions());
        $this->assertCount(1, $results);
        $this->assertSame(CheckResult::STATUS_SUCCESS, $results[0]->getStatus());
    }

    public function testQueueCheck(): void
    {
        $check = new QueueCheck(new ParameterBag(['dbp_api.messenger_unused_messages' => []]));
        $this->assertSame('core.queue', $check->getName());
        $results = $check->check(new CheckOptions());
        $this->assertCount(1, $results);
        $this->assertSame(CheckResult::STATUS_SUCCESS, $results[0]->getStatus());

        $check = new QueueCheck(new ParameterBag(['dbp_api.messenger_unused_messages' => ['something']]));
        $results = $check->check(new CheckOptions());
        $this->assertCount(1, $results);
        $this->assertSame(CheckResult::STATUS_FAILURE, $results[0]->getStatus());
    }

    public function testSymfonyCheck(): void
    {
        $check = new SymfonyCheck(new ParameterBag(['kernel.secret' => 'topsecret', 'kernel.debug' => false]));
        $this->assertSame('core.symfony', $check->getName());
        $results = $check->check(new CheckOptions());
        $this->assertCount(2, $results);
        $this->assertSame(CheckResult::STATUS_SUCCESS, $results[0]->getStatus());
        $this->assertSame(CheckResult::STATUS_SUCCESS, $results[1]->getStatus());

        $check = new SymfonyCheck(new ParameterBag(['kernel.secret' => '', 'kernel.debug' => true]));
        $this->assertSame('core.symfony', $check->getName());
        $results = $check->check(new CheckOptions());
        $this->assertCount(2, $results);
        $this->assertSame(CheckResult::STATUS_FAILURE, $results[0]->getStatus());
        $this->assertSame(CheckResult::STATUS_FAILURE, $results[1]->getStatus());
    }
}
