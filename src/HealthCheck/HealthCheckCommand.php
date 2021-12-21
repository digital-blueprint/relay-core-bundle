<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HealthCheckCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:core:check-health';

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

    protected function configure()
    {
        $this->setDescription('Run all available health checks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = 0;
        foreach ($this->checks as $check) {
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
                    ++$status;
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

        return $status;
    }
}
