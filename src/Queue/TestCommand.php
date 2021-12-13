<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class TestCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:core:queue:test';

    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(MessageBusInterface $bus)
    {
        parent::__construct();

        $this->bus = $bus;
    }

    protected function configure()
    {
        $this->setDescription('Start some dummy tasks for testing');
        $this->addOption('count', null, InputArgument::OPTIONAL, 'The number of messages to send', 1);
        $this->addOption('delay', null, InputArgument::OPTIONAL, 'Delay in seconds', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $input->getOption('count');
        $delay = (int) $input->getOption('delay');

        for ($i = 0; $i < $count; ++$i) {
            if ($delay !== 0) {
                $envelope = new Envelope(new TestMessage(), [new DelayStamp($delay * 1000)]);
            } else {
                $envelope = new Envelope(new TestMessage());
            }
            $this->bus->dispatch($envelope);
        }

        return Command::SUCCESS;
    }
}
