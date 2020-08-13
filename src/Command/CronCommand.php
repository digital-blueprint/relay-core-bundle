<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    protected static $defaultName = 'dbp:cron';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Runs various tasks which need to be executed periodically');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('cache:pool:prune');
        $pruneInput = new ArrayInput([]);
        $pruneOutput = new BufferedOutput();

        return $command->run($pruneInput, $pruneOutput);
    }
}
