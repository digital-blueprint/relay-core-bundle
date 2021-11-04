<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Queue;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:core:queue:restart';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Now run the real messenger:stop-workers command
        $app = $this->getApplication();
        assert($app !== null);
        $command = $app->find('messenger:stop-workers');
        $consumeInput = new ArrayInput([]);

        return $command->run($consumeInput, $output);
    }
}
