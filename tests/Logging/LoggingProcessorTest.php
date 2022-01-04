<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Logging;

use Dbp\Relay\CoreBundle\Logging\LoggingProcessor;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LoggingProcessorTest extends WebTestCase
{
    public function testFilter()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), new RequestStack());

        $record = ['message' => 'http://foo.bar?token=secret'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'http://foo.bar?token=hidden', 'context' => ['relay-session-id' => 'logging-id']], $record);
    }

    public function testRequestId()
    {
        $stack = new RequestStack();
        $stack->push(new Request());
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), $stack);

        $record = ['message' => 'foo'];
        $processed = $processor->__invoke($record);
        $this->assertArrayHasKey('relay-request-id', $processed['context']);
        $processed2 = $processor->__invoke($record);
        $this->assertSame($processed['context']['relay-request-id'], $processed2['context']['relay-request-id']);
    }

    public function testSessionId()
    {
        $processor = new LoggingProcessor(new TestUserSession('log'), new RequestStack());
        $record = ['message' => 'foobar'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'foobar', 'context' => ['relay-session-id' => 'logging-id']], $record);
    }

    public function testMaskUserId()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), new RequestStack());

        $record = [
            'message' => 'hello some-random-user-id!',
            'extra' => ['foo' => 'some-random-user-id'],
            'context' => ['foo' => 'some-random-user-id'],
        ];
        $record = $processor->__invoke($record);
        $this->assertSame([
            'message' => 'hello *****!',
            'extra' => ['foo' => '*****'],
            'context' => ['foo' => '*****', 'relay-session-id' => 'logging-id'], ], $record);

        // Don't mask when contained in a word
        $processor = new LoggingProcessor(new TestUserSession('log'), new RequestStack());
        $record = ['message' => 'logging log'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'logging *****', 'context' => ['relay-session-id' => 'logging-id']], $record);
    }
}
