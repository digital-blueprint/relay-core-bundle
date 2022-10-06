<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DB;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command for migrating the DBs and setting up other things like the worker
 * queue tables etc.
 */
final class MigrateCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:core:migrate';
    private $entityManagers;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new NullLogger();
    }

    protected function configure()
    {
        $this->setDescription('Run all database migrations');
    }

    public function setEntityManagers(array $entityManagers)
    {
        $this->entityManagers = $entityManagers;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->getApplication();
        assert($app !== null);

        // If the messenger uses a DB we can migrate it here too.
        // In theory symfony should do this automatically, but it is broken :(
        // https://github.com/symfony/symfony/issues/47005
        $output->writeln('[Set up the messenger transports]');
        $command = $app->find('messenger:setup-transports');
        $runInput = new ArrayInput([]);
        $runInput->setInteractive($input->isInteractive());
        // Note: in case the transport doesn't have/support auto setup then it will
        // just print a notice, so we don't have to handle it here atm.
        $status = $command->run($input, $output);
        if ($status !== Command::SUCCESS) {
            return $status;
        }

        // The locking system might also create a DB table, but it doesn't expose an easy way
        // to handle it backend independent, so this isn't handled here.

        // Then migrate all registered DBs
        $command = $app->find('doctrine:migrations:migrate');
        if (count($this->entityManagers) === 0) {
            $output->writeln('No entity managers registered, nothing to migrate');
        } else {
            $output->writeln('Running migrations for:');
            foreach ($this->entityManagers as $em) {
                $output->writeln('  '.$em);
            }
        }
        foreach ($this->entityManagers as $em) {
            $output->writeln("Migrating $em:");
            $runInput = new ArrayInput(['--em' => $em]);
            $runInput->setInteractive($input->isInteractive());
            $status = $command->run($runInput, $output);
            if ($status !== Command::SUCCESS) {
                return $status;
            }
        }

        return $status;
    }
}
