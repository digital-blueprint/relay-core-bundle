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
use Symfony\Component\Console\Style\SymfonyStyle;

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

        $io = new SymfonyStyle($input, $output);
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $io->writeln('Running cron jobs at '.$currentTime->format(\DateTime::ATOM));
        $jobs = $this->manager->getDueJobs($force, $currentTime);
        $this->manager->setLastExecutionDate($currentTime);
        $io->writeln('Found '.count($jobs).' due job(s)');
        foreach ($jobs as $job) {
            $io->writeln('<fg=green;options=bold>['.get_class($job).']</>');
            $io->writeln('<fg=blue;options=bold>Name:</> '.$job->getName());
            $io->writeln('<fg=blue;options=bold>Cron:</> '.$job->getInterval());
            $io->writeln('<fg=blue;options=bold>Now:</>  '.$currentTime->format(\DateTime::ATOM));
            $io->writeln('<fg=blue;options=bold>Next:</> '.$this->manager->getNextDate($job, $currentTime)->format(\DateTime::ATOM));
            $name = $job->getName();
            $start = hrtime(true);
            $io->writeln("Running job: $name");

            try {
                $job->run(new CronOptions());
                $duration = (hrtime(true) - $start) / 1e9;
                $io->writeln("Finished successfully in $duration seconds");
            } catch (\Throwable $e) {
                $duration = (hrtime(true) - $start) / 1e9;
                $this->logger->error("cron: '$name' failed after $duration seconds", ['exception' => $e]);
                $io->writeln("<fg=red;options=bold>'$name' failed after $duration seconds: {$e->getMessage()}</>");
            }
        }

        return Command::SUCCESS;
    }
}
