<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is a simpler variant of "messenger:consume". It works with a fixed transport and by requiring a unique
 * worker name works the same across redis and DB transports.
 *
 * It also sets some nice defaults for everything.
 */
class WorkCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:queue:work';

    /**
     * @var TransportFactoryDecorator
     */
    private $transportFactory;

    public function __construct(TransportFactoryDecorator $transportFactory)
    {
        parent::__construct();
        $this->transportFactory = $transportFactory;
    }

    protected function configure(): void
    {
        $this->addArgument('worker-name', InputArgument::REQUIRED, 'A unique and stable worker name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // The question is why can't we use a random worker name based on the hostname or pid etc.
        // https://github.com/symfony/symfony-docs/pull/11869/files explains why consumer names
        // should be unique, reused, and ideally stable, when using redis. By requiring this for all transports
        // we keep the config/docs simple.
        $workerName = $input->getArgument('worker-name');
        $this->transportFactory->setActiveWorkerName($workerName);

        // Now run the real messenger:consume command
        $app = $this->getApplication();
        assert($app !== null);
        $command = $app->find('messenger:consume');
        // use some good default limits, since lots of php code leaks the workers have to be restarted from time to time
        $consumeInput = new ArrayInput([
            '--time-limit' => (string) Utils::DEFAULT_TIME_LIMIT,
            '--limit' => (string) Utils::DEFAULT_TASK_LIMIT,
            'receivers' => [Utils::QUEUE_TRANSPORT_NAME],
        ]);

        return $command->run($consumeInput, $output);
    }
}
