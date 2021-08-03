<?php

declare(strict_types=1);

use Dbp\Relay\CoreBundle\Service\LoggingProcessor;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoggingProcessorTest extends WebTestCase
{
    public function testFilter()
    {
        $processor = new LoggingProcessor(new TestUserSession());

        $record = ['message' => 'http://foo.bar?token=secret'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'http://foo.bar?token=hidden', 'context' => ['dbp-id' => 'logging-id']], $record);
    }
}
