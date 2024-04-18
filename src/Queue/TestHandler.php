<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TestHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __invoke(TestMessage $message)
    {
        $this->logger->info('Handled test message');
    }
}
