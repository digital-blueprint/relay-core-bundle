<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\Cron\CronManager;
use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Psr\Cache\CacheItemPoolInterface;

class CronCheck implements CheckInterface
{
    private ?CacheItemPoolInterface $cachePool;

    public function __construct(private CronManager $cronManager)
    {
    }

    public function setCache(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    public function getName(): string
    {
        return 'core.cron';
    }

    public function check(CheckOptions $options): array
    {
        $result = new CheckResult('Check if cron jobs are running');

        if ($this->cronManager->getJobs() === []) {
            $result->set(CheckResult::STATUS_SUCCESS, 'No cron jobs registered');

            return [$result];
        }

        // In case of a cache clear we don't get a last execution time, so just store the first check
        // time as a reference. If there is no execution one hour after the first check we also error out.
        assert($this->cachePool instanceof CacheItemPoolInterface);
        $item = $this->cachePool->getItem('health-check-cron-first-check');
        $firstCheckDate = $item->get();
        if ($firstCheckDate === null) {
            $firstCheckDate = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
            $item->set($firstCheckDate);
            $this->cachePool->save($item);
        }

        $lastExecutionDate = $this->cronManager->getLastExecutionDate();
        if ($lastExecutionDate !== null) {
            if ($lastExecutionDate->getTimestamp() < (time() - 3600)) {
                $result->set(CheckResult::STATUS_FAILURE, 'Cron manager hasn\'t run in more than one hour');
            } else {
                $result->set(CheckResult::STATUS_SUCCESS, 'Cron manager has run within the last hour');
            }
        } else {
            if ($firstCheckDate->getTimestamp() < (time() - 3600)) {
                $result->set(CheckResult::STATUS_FAILURE, 'Cron manager has never run');
            } else {
                $result->set(CheckResult::STATUS_SUCCESS, 'Waiting for first cron execution (started monitoring)');
            }
        }

        return [$result];
    }
}
