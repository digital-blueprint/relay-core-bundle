<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Cron\CronExpression;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class CronManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @var CronJobInterface[]
     */
    private $jobs;

    public function __construct()
    {
        $this->jobs = [];
        $this->logger = new NullLogger();
    }

    public function setCache(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    public function addJob(CronJobInterface $job)
    {
        $this->jobs[] = $job;
    }

    /**
     * Returns if a job should run or not. Note that there is no feedback channel, so if you skip
     * this run you will only be notified the next time the cron job should run.
     *
     * @param string $cronExpression A cron expression
     *
     * @return bool If the job should run
     */
    public static function isDue(?\DateTimeInterface $previousRun, \DateTimeInterface $currentRun, string $cronExpression): bool
    {
        $cron = new CronExpression($cronExpression);
        $previousExpectedRun = $cron->getPreviousRunDate($currentRun, 0, true);
        $previousExpectedRun->setTimezone(new \DateTimeZone('UTC'));

        $shouldRun = false;
        // If we were scheduled to run between now and the previous run (or just before of no previous run exists)
        // then we should run
        if ($previousExpectedRun <= $currentRun && ($previousRun === null || $previousExpectedRun > $previousRun)) {
            $shouldRun = true;
        }

        return $shouldRun;
    }

    public function getPreviousRun(\DateTimeInterface $currentTime): ?\DateTimeInterface
    {
        $cachePool = $this->cachePool;
        // Store the previous run time in the cache and fetch from there
        assert($cachePool instanceof CacheItemPoolInterface);
        $item = $cachePool->getItem('cron-previous-run');
        $value = $item->get();
        $previousRun = null;
        if ($value !== null) {
            $previousRun = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp($value);
            if ($previousRun > $currentTime) {
                // Something is wrong, cap at the current time
                $previousRun = $currentTime;
            }
        }
        $item->set($currentTime->getTimestamp());
        if ($cachePool->save($item) === false) {
            throw new \RuntimeException('Saving cron timestamp failed');
        }

        return $previousRun;
    }

    /**
     * @return CronJobInterface[]
     */
    public function getAllJobs(): array
    {
        return $this->jobs;
    }

    /**
     * @return CronJobInterface[]
     */
    public function getDueJobs(): array
    {
        // Get all jobs that should have been run between the last time we were called and now
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        // round to full seconds, so we have the same resolution for both date times
        $currentTime = $currentTime->setTimestamp($currentTime->getTimestamp());
        $previousRunTime = $this->getPreviousRun($currentTime);

        $toRun = [];
        foreach ($this->jobs as $job) {
            $interval = $job->getInterval();
            $name = $job->getName();
            $this->logger->info("cron: Checking '$name' ($interval)");
            $isDue = self::isDue($previousRunTime, $currentTime, $interval);
            if ($isDue) {
                $toRun[] = $job;
            }
        }

        return $toRun;
    }
}
