<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Logging;

use Dbp\Relay\CoreBundle\Logging\LoggingProcessor;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Monolog\Logger;
use Monolog\LogRecord;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LoggingProcessorTest extends WebTestCase
{
    private function processRecord(LoggingProcessor $processor, array $record): array
    {
        // set some defaults
        $record['level'] ??= 100;
        $record['level_name'] ??= 'DEBUG';
        $record['datetime'] ??= new \DateTimeImmutable();

        // Paper over the monolog v2 vs v3 differences and only work with arrays
        if (Logger::API !== 2) {
            $record = new LogRecord(
                $record['datetime'], $record['channel'], Logger::toMonologLevel($record['level']),
                $record['message'], $record['context'] ?? [], $record['extra'] ?? []);

            return $processor->__invoke($record)->toArray();
        } else {
            return $processor->__invoke($record);
        }
    }

    public function testAllFieldsArePreserved()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id', isAuthenticated: true), new RequestStack());
        $now = new \DateTimeImmutable();
        $record = [
            'message' => 'message', 'channel' => 'channel', 'datetime' => $now,
            'level' => 400, 'context' => ['foo' => 'bar'], 'extra' => ['fooextra' => 'barextra']];
        $record = $this->processRecord($processor, $record);
        $this->assertSame('message', $record['message']);
        $this->assertSame('channel', $record['channel']);
        $this->assertSame($now, $record['datetime']);
        $this->assertSame(400, $record['level']);
        $this->assertSame(['foo' => 'bar', 'relay-session-id' => 'logging-id'], $record['context']);
        $this->assertSame(['fooextra' => 'barextra'], $record['extra']);
    }

    public function testFilter()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id', isAuthenticated: true), new RequestStack());

        $record = ['message' => 'http://foo.bar?token=secret', 'channel' => 'app'];
        $record = $this->processRecord($processor, $record);
        $this->assertSame('http://foo.bar?token=hidden', $record['message']);
        $this->assertSame('app', $record['channel']);
        $this->assertSame(['relay-session-id' => 'logging-id'], $record['context']);
    }

    public function testRequestId()
    {
        $stack = new RequestStack();
        $stack->push(new Request());
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id'), $stack);

        $record = ['message' => 'foo', 'channel' => 'app'];
        $processed = $this->processRecord($processor, $record);
        $this->assertArrayHasKey('relay-request-id', $processed['context']);
        $processed2 = $this->processRecord($processor, $record);
        $this->assertSame($processed['context']['relay-request-id'], $processed2['context']['relay-request-id']);
    }

    public function testSessionId()
    {
        $processor = new LoggingProcessor(new TestUserSession('log', isAuthenticated: true), new RequestStack());
        $record = ['message' => 'foobar', 'channel' => 'app'];
        $record = $this->processRecord($processor, $record);
        $this->assertSame('foobar', $record['message']);
        $this->assertSame('app', $record['channel']);
        $this->assertSame(['relay-session-id' => 'logging-id'], $record['context']);
    }

    public function testRoute()
    {
        $stack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'some_route');
        $stack->push($request);
        $processor = new LoggingProcessor(new TestUserSession('log'), $stack);
        $record = ['message' => 'foobar', 'channel' => 'app'];
        $record = $this->processRecord($processor, $record);
        $this->assertSame('some_route', $record['context']['relay-route']);
    }

    public function testMaskUserId()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id', isAuthenticated: true), new RequestStack());

        $record = [
            'message' => 'hello some-random-user-id!',
            'extra' => ['foo' => 'some-random-user-id'],
            'context' => ['foo' => 'some-random-user-id'],
            'channel' => 'app',
        ];
        $record = $this->processRecord($processor, $record);
        $this->assertSame('hello *****!', $record['message']);
        $this->assertSame(['foo' => '*****'], $record['extra']);
        $this->assertSame(['foo' => '*****', 'relay-session-id' => 'logging-id'], $record['context']);
        $this->assertSame('app', $record['channel']);

        // Don't mask when contained in a word
        $processor = new LoggingProcessor(new TestUserSession('log', isAuthenticated: true), new RequestStack());
        $record = ['message' => 'logging log', 'channel' => 'app'];
        $record = $this->processRecord($processor, $record);
        $this->assertSame('logging *****', $record['message']);
        $this->assertSame('app', $record['channel']);
        $this->assertSame(['relay-session-id' => 'logging-id'], $record['context']);
    }

    public function testNoMasking()
    {
        $processor = new LoggingProcessor(new TestUserSession('some-random-user-id', isAuthenticated: true), new RequestStack());
        $record = [
            'message' => 'hello some-random-user-id!',
            'channel' => 'mychannel',
        ];
        $result = $this->processRecord($processor, $record);
        $this->assertSame('hello *****!', $result['message']);

        $processor->setMaskConfig(['mychannel' => true]);
        $result = $this->processRecord($processor, $record);
        $this->assertSame('hello *****!', $result['message']);

        $processor->setMaskConfig(['mychannel' => false]);
        $result = $this->processRecord($processor, $record);
        $this->assertSame('hello some-random-user-id!', $result['message']);
    }
}
