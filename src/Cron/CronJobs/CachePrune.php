<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron\CronJobs;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CachePrune implements CronJobInterface
{
    private static $cmd;

    public function getName(): string
    {
        return 'Prune Caches';
    }

    public static function setPruneCommand(Command $cmd)
    {
        self::$cmd = $cmd;
    }

    public function getInterval(): string
    {
        // Every hour
        return '0 * * * *';
    }

    public function run(CronOptions $options): void
    {
        $pruneInput = new ArrayInput([]);
        $pruneOutput = new BufferedOutput();

        $status = self::$cmd->run($pruneInput, $pruneOutput);
        if ($status !== 0) {
            throw new \RuntimeException("prune failed with: $status");
        }
    }
}
