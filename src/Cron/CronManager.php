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
     * @return bool If the job should run
     */
    public static function isDue(CronJobInterface $job, ?\DateTimeInterface $previousRun, \DateTimeInterface $currentRun): bool
    {
        $cron = new CronExpression($job->getInterval());
        $previousExpectedRun = $cron->getPreviousRunDate($currentRun, 0, true);
        $previousExpectedRun->setTimezone(new \DateTimeZone('UTC'));

        $shouldRun = false;
        if ($previousRun === null) {
            // In case there is no previous run we just skip the cron job
            // This can happen on re-deployments, and we don't want a cron-storm there, or jobs that run
            // way off their schedule
            $shouldRun = false;
        } elseif ($previousExpectedRun->getTimestamp() > $previousRun->getTimestamp() && $previousExpectedRun->getTimestamp() <= $currentRun->getTimestamp()) {
            // If we were scheduled to run between now and right the previous run then we should run
            // XXX: We compare the timestamps, since that is what we use to serialize the last execution time (so we get the same rounding)
            $shouldRun = true;
        }

        return $shouldRun;
    }

    /**
     * Returns the date and time the job is scheduled to run the next time.
     */
    public static function getNextDate(CronJobInterface $job, \DateTimeInterface $currentTime): \DateTimeInterface
    {
        $cronExpression = $job->getInterval();
        $cron = new CronExpression($cronExpression);
        $nextDate = $cron->getNextRunDate($currentTime, 0, true);
        $nextDate->setTimezone(new \DateTimeZone('UTC'));

        return \DateTimeImmutable::createFromMutable($nextDate);
    }

    /**
     * Returns the last time cron was executed.
     */
    public function getLastExecutionDate(): ?\DateTimeInterface
    {
        $cachePool = $this->cachePool;
        assert($cachePool instanceof CacheItemPoolInterface);
        $item = $cachePool->getItem('cron-previous-run');
        $value = $item->get();
        $previousRun = null;
        if ($value !== null) {
            $previousRun = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp($value);
        }

        return $previousRun;
    }

    /**
     * Stores the given time as the new last cron execution time.
     */
    public function setLastExecutionDate(\DateTimeInterface $currentTime): void
    {
        $cachePool = $this->cachePool;
        assert($cachePool instanceof CacheItemPoolInterface);
        $item = $cachePool->getItem('cron-previous-run');
        $item->set($currentTime->getTimestamp());
        if ($cachePool->save($item) === false) {
            throw new \RuntimeException('Saving cron timestamp failed');
        }
    }

    /**
     * @return CronJobInterface[]
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    public function runDueJobs(bool $force = false, \DateTimeInterface $currentTime = null)
    {
        // Get all jobs that should have been run between the last time we were called and now
        if ($currentTime === null) {
            $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }
        $lastDate = $this->getLastExecutionDate();
        $this->setLastExecutionDate($currentTime);

        if ($lastDate === null) {
            $this->logger->info('cron: No last execution time available, will no run anything');
        }

        $toRun = [];
        foreach ($this->jobs as $job) {
            $interval = $job->getInterval();
            $name = $job->getName();
            $this->logger->info("cron: Checking '$name' ($interval)");
            $isDue = self::isDue($job, $lastDate, $currentTime);
            if ($isDue || $force) {
                $toRun[] = $job;
            }
        }

        if (count($toRun) === 0) {
            $this->logger->info('cron: No jobs to run');
        }

        foreach ($toRun as $job) {
            $name = $job->getName();
            $this->logger->info("cron: Running '$name'");
            try {
                $job->run(new CronOptions());
            } catch (\Throwable $e) {
                $this->logger->error("cron: '$name' failed", ['exception' => $e]);
            }
        }
    }
}
