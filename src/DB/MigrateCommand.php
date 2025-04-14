<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DB;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

/**
 * A command for migrating the DBs and setting up other things like the worker
 * queue tables etc.
 */
final class MigrateCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $entityManagers = [];

    public function __construct(
        private readonly KernelInterface $appKernel,
        private readonly EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
        $this->logger = new NullLogger();
    }

    protected function configure(): void
    {
        $this->setName('dbp:relay:core:migrate');
        $this->setDescription('Run all database migrations');
    }

    public function setEntityManagers(array $entityManagers): void
    {
        $this->entityManagers = $entityManagers;
    }

    /**
     * Runs a console command in a separate process using the same PHP executable while writing output to $output.
     * This is required because some commands like doctrine:migrations:migrate break when called
     * with different settings in the same process (the connection doesn't despite a different --em being passed).
     * This isolates the different calls.
     *
     * The goal is to be a close as possible to the behaviour of invoking a symfony command in-process, but
     * with isolation between multiple commands.
     *
     * This will throw in case the command doesn't return 0;
     *
     * This assumes <project-dir>/bin/console exists (which it hopefully does)
     */
    public function runConsoleCommand(array $inputArgs, InputInterface $input, OutputInterface $output): void
    {
        // Ensure everything is where we expect it to be
        $filesystem = new Filesystem();
        $console = $this->appKernel->getProjectDir().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console';
        if (!$filesystem->exists($console)) {
            throw new \RuntimeException("$console doesn't exist");
        }
        if (!$filesystem->exists(PHP_BINARY)) {
            throw new \RuntimeException(PHP_BINARY." doesn't exist");
        }

        // Build a console command
        $args = [PHP_BINARY, $console];
        $isTty = true;
        if (!$input->isInteractive()) {
            $args[] = '--no-interaction';
            $isTty = false;
        }
        $args = array_merge($args, $inputArgs);

        // Run the command
        $output->writeln('Running command: '.implode(' ', $args));
        $process = new Process($args);
        // Set a timeout of 20 minutes, database migrations can take a while!
        $process->setTimeout(1200);
        $process->setTty($isTty);
        $process->mustRun(function ($type, $buffer) use ($output) {
            $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            if ($type === Process::ERR) {
                $errOutput->write($buffer);
            } else {
                $output->write($buffer);
            }
        });
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->getApplication();
        assert($app !== null);

        // If the messenger uses a DB we can migrate it here too.
        // In theory symfony should do this automatically, but it is broken :(
        // https://github.com/symfony/symfony/issues/47005
        $output->writeln('[Set up the messenger transports]');
        // Note: in case the transport doesn't have/support auto setup then it will
        // just print a notice, so we don't have to handle it here atm.
        $this->runConsoleCommand(['messenger:setup-transports'], $input, $output);

        // The locking system might also create a DB table, but it doesn't expose an easy way
        // to handle it backend independent, so this isn't handled here.

        // Then migrate all registered DBs
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
            $this->runConsoleCommand(['doctrine:migrations:migrate', '--em',  $em], $input, $output);
        }

        $migratePostEvent = new MigratePostEvent($output);
        $this->eventDispatcher->dispatch($migratePostEvent);

        return Command::SUCCESS;
    }
}
