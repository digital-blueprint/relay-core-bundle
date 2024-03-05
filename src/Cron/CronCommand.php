<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobs\CachePrune;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CronCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var CronManager
     */
    private $manager;

    public function __construct(CronManager $manager)
    {
        parent::__construct();
        $this->logger = new NullLogger();
        $this->manager = $manager;
    }

    protected function configure(): void
    {
        $this->setName('dbp:relay:core:cron');
        // dbp:cron only for backwards compat
        $this->setAliases(['dbp:cron']);
        $this->setDescription('Runs various tasks which need to be executed periodically');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Run the cron job even if it\'s not due');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // We need to pass the prune command to CachePrune since I didn't find an alternative
        $app = $this->getApplication();
        $force = $input->getOption('force');
        assert($app !== null);
        $command = $app->find('cache:pool:prune');
        CachePrune::setPruneCommand($command);

        $this->manager->runDueJobs($force);

        return 0;
    }
}
