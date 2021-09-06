<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Cron\CronExpression;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class CronEvent extends Event implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const NAME = 'dbp.relay.cron';

    private $previousRun;
    private $currentRun;

    public function __construct(?\DateTimeInterface $previousRun, \DateTimeInterface $currentRun)
    {
        $this->previousRun = $previousRun;
        $this->currentRun = $currentRun;
    }

    /**
     * Returns if the listener should run its job or not. Note that there is no feedback channel, so if you skip
     * this run you will only be notified the next time the cron job should run.
     *
     * @param string $id             A unique ID for logging, book keeping
     * @param string $cronExpression A cron expression
     *
     * @return bool If the job should run
     */
    public function isDue(string $id, string $cronExpression): bool
    {
        $cron = new CronExpression($cronExpression);
        $previousExpectedRun = $cron->getPreviousRunDate($this->currentRun, 0, true);
        $previousExpectedRun->setTimezone(new \DateTimeZone('UTC'));

        $shouldRun = false;
        // If we were scheduled to run between now and the previous run (or jsut before of no previous run exists)
        // then we should run
        if ($previousExpectedRun <= $this->currentRun && ($this->previousRun === null || $previousExpectedRun > $this->previousRun)) {
            $shouldRun = true;
        }

        if ($this->logger !== null && $shouldRun) {
            $this->logger->debug("Running cron job for '$id' ($cronExpression)");
        }

        return $shouldRun;
    }
}
