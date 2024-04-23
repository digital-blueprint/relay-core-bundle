<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class TestCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(MessageBusInterface $bus)
    {
        parent::__construct();

        $this->bus = $bus;
    }

    protected function configure(): void
    {
        $this->setName('dbp:relay:core:queue:test');
        $this->setDescription('Start some dummy tasks for testing');
        $this->addOption('count', null, InputOption::VALUE_OPTIONAL, 'The number of messages to send', 1);
        $this->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'Delay in seconds', 0);
        $this->addOption('fail', null, InputOption::VALUE_NONE, 'Throw an exception when executing the task');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $input->getOption('count');
        $delay = (int) $input->getOption('delay');
        $fail = $input->getOption('fail');

        for ($i = 0; $i < $count; ++$i) {
            if ($delay !== 0) {
                $envelope = new Envelope(new TestMessage($fail, $i + 1), [new DelayStamp($delay * 1000)]);
            } else {
                $envelope = new Envelope(new TestMessage($fail, $i + 1));
            }
            $this->bus->dispatch($envelope);
        }

        return Command::SUCCESS;
    }
}
