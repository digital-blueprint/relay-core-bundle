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

        $record = ['message' => 'http://foo.bar?token=secret', 'channel' => 'app'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'http://foo.bar?token=hidden', 'channel' => 'app', 'context' => ['relay-session-id' => 'logging-id']], $record);
    }

    public function testRequestId()
    {
        $stack = new RequestStack();
        $stack->push(new Request());
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), $stack);

        $record = ['message' => 'foo', 'channel' => 'app'];
        $processed = $processor->__invoke($record);
        $this->assertArrayHasKey('relay-request-id', $processed['context']);
        $processed2 = $processor->__invoke($record);
        $this->assertSame($processed['context']['relay-request-id'], $processed2['context']['relay-request-id']);
    }

    public function testSessionId()
    {
        $processor = new LoggingProcessor(new TestUserSession('log'), new RequestStack());
        $record = ['message' => 'foobar', 'channel' => 'app'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'foobar', 'channel' => 'app', 'context' => ['relay-session-id' => 'logging-id']], $record);
    }

    public function testRoute()
    {
        $stack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'some_route');
        $stack->push($request);
        $processor = new LoggingProcessor(new TestUserSession('log'), $stack);
        $record = ['message' => 'foobar', 'channel' => 'app'];
        $record = $processor->__invoke($record);
        $this->assertSame('some_route', $record['context']['relay-route']);
    }

    public function testMaskUserId()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), new RequestStack());

        $record = [
            'message' => 'hello some-random-user-id!',
            'extra' => ['foo' => 'some-random-user-id'],
            'context' => ['foo' => 'some-random-user-id'],
            'channel' => 'app',
        ];
        $record = $processor->__invoke($record);
        $this->assertSame([
            'message' => 'hello *****!',
            'extra' => ['foo' => '*****'],
            'context' => ['foo' => '*****', 'relay-session-id' => 'logging-id'],
            'channel' => 'app', ], $record);

        // Don't mask when contained in a word
        $processor = new LoggingProcessor(new TestUserSession('log'), new RequestStack());
        $record = ['message' => 'logging log', 'channel' => 'app'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'logging *****', 'channel' => 'app', 'context' => ['relay-session-id' => 'logging-id']], $record);
    }

    public function testNoMasking()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), new RequestStack());
        $record = [
            'message' => 'hello some-random-user-id!',
            'channel' => 'mychannel',
        ];
        $result = $processor->__invoke($record);
        $this->assertSame('hello *****!', $result['message']);

        $processor->setMaskConfig(['mychannel' => true]);
        $result = $processor->__invoke($record);
        $this->assertSame('hello *****!', $result['message']);

        $processor->setMaskConfig(['mychannel' => false]);
        $result = $processor->__invoke($record);
        $this->assertSame('hello some-random-user-id!', $result['message']);
    }
}
