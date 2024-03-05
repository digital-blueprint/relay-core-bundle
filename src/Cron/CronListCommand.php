<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CronListCommand extends Command implements LoggerAwareInterface
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
        $this->setName('dbp:relay:core:cron:list');
        $this->setDescription('Lists all registered cron jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $jobs = $this->manager->getJobs();
        foreach ($jobs as $job) {
            $output->writeln('<fg=green;options=bold>['.get_class($job).']</>');
            $output->writeln('<fg=blue;options=bold>Name:</> '.$job->getName());
            $output->writeln('<fg=blue;options=bold>Cron:</> "'.$job->getInterval().'"');
            $output->writeln('<fg=blue;options=bold>Now:</> '.$currentTime->format(\DateTime::ATOM));
            $output->writeln('<fg=blue;options=bold>Next:</> '.$this->manager->getNextDate($job, $currentTime)->format(\DateTime::ATOM));
        }

        return 0;
    }
}
