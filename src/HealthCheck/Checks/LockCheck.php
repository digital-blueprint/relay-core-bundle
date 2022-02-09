<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Symfony\Component\Lock\LockFactory;

class LockCheck implements CheckInterface
{
    private $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    public function getName(): string
    {
        return 'core.lock';
    }

    public function check(CheckOptions $options): array
    {
        $result = new CheckResult('Check if the locking works');

        $result->set(CheckResult::STATUS_SUCCESS);
        try {
            $lock = $this->lockFactory->createLock('health-check');
            $lock->acquire();
            $lock->refresh();
            $lock->release();
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);
        }

        return [$result];
    }
}
