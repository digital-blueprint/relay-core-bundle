<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Logging;

use Dbp\Relay\CoreBundle\Logging\LoggingProcessor;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoggingProcessorTest extends WebTestCase
{
    public function testFilter()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'));

        $record = ['message' => 'http://foo.bar?token=secret'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'http://foo.bar?token=hidden', 'context' => ['dbp-id' => 'logging-id']], $record);
    }

    public function testMaskUserId()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'));

        $record = [
            'message' => 'hello some-random-user-id!',
            'extra' => ['foo' => 'some-random-user-id'],
            'context' => ['foo' => 'some-random-user-id'],
        ];
        $record = $processor->__invoke($record);
        $this->assertSame([
            'message' => 'hello *****!',
            'extra' => ['foo' => '*****'],
            'context' => ['foo' => '*****', 'dbp-id' => 'logging-id'], ], $record);

        // Don't mask when contained in a word
        $processor = new LoggingProcessor(new TestUserSession('log'));
        $record = ['message' => 'logging log'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'logging *****', 'context' => ['dbp-id' => 'logging-id']], $record);
    }
}
