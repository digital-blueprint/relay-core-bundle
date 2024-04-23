<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Queue;

use Dbp\Relay\CoreBundle\Queue\TestHandler;
use Dbp\Relay\CoreBundle\Queue\TestMessage;
use Dbp\Relay\CoreBundle\Queue\TransportFactoryDecorator;
use Dbp\Relay\CoreBundle\Queue\Utils;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;

class QueueTest extends KernelTestCase
{
    public function testHandler()
    {
        $handler = new TestHandler();
        $handler->setLogger(new NullLogger());
        $handler->__invoke(new TestMessage());
        $this->assertTrue(true);
    }

    public function testHandlerFail()
    {
        $handler = new TestHandler();
        $handler->setLogger(new NullLogger());
        $this->expectException(\RuntimeException::class);
        $handler->__invoke(new TestMessage(fail: true));
    }

    public function testTransportFactoryDecorator()
    {
        $factory = new TransportFactoryDecorator(new SyncTransportFactory(new MessageBus()));
        $factory->setLogger(new NullLogger());
        $factory->setActiveWorkerName('foo');
        $this->assertFalse($factory->supports('in-memory://whatever', []));
        $this->assertTrue($factory->supports('sync://', []));

        $this->assertNotNull($factory->createTransport('in-memory://whatever', [], new Serializer()));

        $options = [
            'transport_name' => Utils::QUEUE_TRANSPORT_NAME,
        ];
        $this->assertNotNull($factory->createTransport('in-memory://dummy-queue-not-configured', $options, new Serializer()));
        $this->assertNotNull($factory->createTransport('redis://localhost:6379/messages', $options, new Serializer()));
        $this->assertNotNull($factory->createTransport('doctrine://default', $options, new Serializer()));

        // We don't support anything else atm
        $this->expectExceptionMessage('Only redis and doctrine currently supported as a queue transport');
        $this->assertNotNull($factory->createTransport('amqp://localhost', $options, new Serializer()));
    }
}
