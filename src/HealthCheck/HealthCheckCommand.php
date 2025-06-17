<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCheckCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var CheckInterface[]
     */
    private $checks;

    public function __construct()
    {
        parent::__construct();
        $this->checks = [];
    }

    public function addCheck(CheckInterface $check)
    {
        $this->checks[] = $check;
    }

    protected function configure(): void
    {
        $this->setName('dbp:relay:core:check-health');
        $this->setDescription('Run all available health checks');
        $this
            ->addOption(
                'only',
                null,
                InputOption::VALUE_REQUIRED,
                'Run only specific health checks (comma-separated list)'
            );
        $this->addOption(
            'list-checks',
            null,
            InputOption::VALUE_NONE,
            'List all available health checks'
        );
    }

    /**
     * For testing.
     */
    public function setChecks(array $checks): void
    {
        $this->checks = $checks;
    }

    private function listAvailableChecks(SymfonyStyle $io): void
    {
        $io->title('Available Health Checks');

        $rows = [];
        foreach ($this->checks as $check) {
            $rows[] = [$check->getName()];
        }
        asort($rows);

        $io->table(['Check Name'], $rows);
    }

    private function determineChecksToRun(InputInterface $input, SymfonyStyle $io): ?array
    {
        $onlyOption = $input->getOption('only');

        if ($onlyOption === null) {
            return $this->checks;
        }

        $requestedCheckNames = array_map('trim', explode(',', $onlyOption));
        $requestedCheckNames = array_filter($requestedCheckNames);

        if ($requestedCheckNames === []) {
            $io->error('No health checks specified in --only option.');

            return null;
        }

        $availableChecks = [];
        foreach ($this->checks as $check) {
            $availableChecks[$check->getName()] = $check;
        }

        $checksToRun = [];
        $invalidChecks = [];

        foreach ($requestedCheckNames as $checkName) {
            if (isset($availableChecks[$checkName])) {
                $checksToRun[] = $availableChecks[$checkName];
            } else {
                $invalidChecks[] = $checkName;
            }
        }

        if (!empty($invalidChecks)) {
            $io->error(sprintf(
                'Invalid health check(s): %s. Available checks: %s',
                implode(', ', $invalidChecks),
                implode(', ', array_keys($availableChecks))
            ));

            return null;
        }

        return $checksToRun;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list-checks')) {
            $this->listAvailableChecks($io);

            return Command::SUCCESS;
        }

        $checksToRun = $this->determineChecksToRun($input, $io);
        if ($checksToRun === null) {
            return Command::FAILURE;
        }

        $failed = [];
        foreach ($checksToRun as $check) {
            $output->writeln('<fg=green;options=bold>['.$check->getName().']</>');
            $results = $check->check(new CheckOptions());
            foreach ($results as $result) {
                $color = '';
                $output->write('  <fg=cyan>'.$result->getDescription().': </>');
                if ($result->getStatus() === CheckResult::STATUS_SUCCESS) {
                    $color = 'green';
                } elseif ($result->getStatus() === CheckResult::STATUS_WARNING) {
                    $color = 'yellow';
                } elseif ($result->getStatus() === CheckResult::STATUS_FAILURE) {
                    $color = 'red';
                    $failed[] = [$check, $result];
                } else {
                    assert(0);
                }
                $output->write('<fg='.$color.';options=bold>['.$result->getStatus().']</>');
                if ($result->getMessage() !== null) {
                    $output->write(' <fg='.$color.'>'.$result->getMessage().'</>');
                }
                $output->writeln('');
            }
        }

        if ($failed) {
            $output->writeln('<fg=red;options=bold>The following checks failed:</>');
            foreach ($failed as [$check, $result]) {
                $output->writeln('<options=bold>['.$check->getName().']</> '.$result->getDescription());
            }
        }

        return count($failed);
    }
}
