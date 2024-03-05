<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Decorated because we want to set reasonable defaults while still allowing the user to set a full transport
 * DSN if needed.
 */
class TransportFactoryDecorator implements TransportFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TransportFactoryInterface
     */
    private $decorated;

    /**
     * @var string
     */
    private $workerName;

    public function __construct(TransportFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->workerName = 'worker';
    }

    public function setActiveWorkerName(string $name): void
    {
        $this->workerName = $name;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        // In case we build our main transport we make sure the defaults are set to our liking.
        // The DSN content still wins, but ideally the user only sets the minimal DSN.
        if ($options['transport_name'] === Utils::QUEUE_TRANSPORT_NAME) {
            $this->logger->debug("Creating queue transport for worker: '$this->workerName'");

            $redis = new RedisTransportFactory();
            if ($redis->supports($dsn, $options)) {
                // We set some nice namespaced default, so the user doesn't have to care about potential conflicts
                $options['stream'] = 'dbp_relay_queue_stream';
                $options['group'] = 'dbp_relay_queue_group';
                $options['consumer'] = $this->workerName;
                // Use the new recommended default:
                // https://github.com/symfony/symfony/pull/42163
                $options['delete_after_ack'] = true;
            } elseif (strpos($dsn, 'doctrine://') === 0) {
                $options['queue_name'] = 'main';
            } elseif ($dsn === 'in-memory://dummy-queue-not-configured') {
                // This is used when no queue is configured, so allow it.
            } else {
                throw new \Exception('Only redis and doctrine currently supported as a queue transport (current DSN: '.$dsn.')');
            }
        }

        return $this->decorated->createTransport($dsn, $options, $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return $this->decorated->supports($dsn, $options);
    }
}
