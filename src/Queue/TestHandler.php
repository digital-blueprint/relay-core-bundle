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
        $this->logger->info('Start handling of test message '.$message->number);
        if ($message->fail) {
            throw new \RuntimeException('Message '.$message->number.' failed!');
        }
        $this->logger->info('Handled test message '.$message->number);
    }
}
