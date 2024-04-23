<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Decorated because we want to set reasonable defaults while still allowing the user to set a full transport
 * DSN if needed.
 *
 * @psalm-suppress MissingTemplateParam
 */
class TransportFactoryDecorator implements TransportFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TransportFactoryInterface
     */
    private $decorated;

    /**
     * @var ?string
     */
    private $workerName;

    public function __construct(TransportFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->workerName = null;
    }

    public function setActiveWorkerName(string $name): void
    {
        $this->workerName = $name;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $isWorkerTransport = ($options['transport_name'] ?? null) === Utils::QUEUE_TRANSPORT_NAME;
        $isFailedTransport = ($options['transport_name'] ?? null) === Utils::QUEUE_TRANSPORT_FAILED_NAME;

        // In case we build our main transport we make sure the defaults are set to our liking.
        // The DSN content still wins, but ideally the user only sets the minimal DSN.
        if ($isWorkerTransport || $isFailedTransport) {
            $transportName = $options['transport_name'];
            $workerName = $this->workerName ?? Uuid::v4()->toRfc4122();
            $this->logger->debug("Creating queue transport ('$transportName') for worker: '$workerName'");

            if (str_starts_with($dsn, 'redis:') || str_starts_with($dsn, 'rediss:')) {
                // We set some nice namespaced default, so the user doesn't have to care about potential conflicts
                if ($isWorkerTransport) {
                    $options['stream'] = 'dbp_relay_queue_stream';
                    $options['group'] = 'dbp_relay_queue_group';
                } else {
                    $options['stream'] = 'dbp_relay_queue_stream_failed';
                    $options['group'] = 'dbp_relay_queue_group_failed';
                }
                $options['consumer'] = $workerName;

                // Use the new recommended default:
                // https://github.com/symfony/symfony/pull/42163
                $options['delete_after_ack'] = true;
            } elseif (strpos($dsn, 'doctrine://') === 0) {
                if ($isFailedTransport) {
                    $options['queue_name'] = 'main_failed';
                } else {
                    $options['queue_name'] = 'main';
                }
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
