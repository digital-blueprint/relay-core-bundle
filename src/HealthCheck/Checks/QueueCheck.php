<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QueueCheck implements CheckInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'core.queue';
    }

    public function check(CheckOptions $options): array
    {
        $result = new CheckResult('Check if the queue is configured');

        $unusedMessages = $this->container->getParameter('dbp_api.messenger_unused_messages');
        if (count($unusedMessages)) {
            $result->set(
                CheckResult::STATUS_FAILURE,
                'A bundle requires a worker queue: set "queue_dsn" in the "dbp_relay_core" bundle config',
                ['messages' => $unusedMessages]);
        } else {
            $result->set(CheckResult::STATUS_SUCCESS);
        }

        return [$result];
    }
}
