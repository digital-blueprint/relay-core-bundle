<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\HealthCheck;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;

class DummyCheck implements CheckInterface
{
    public function getName(): string
    {
        return 'Dummy Check';
    }

    public function check(CheckOptions $options): array
    {
        $results = [];
        $result = new CheckResult('success');
        $result->set(CheckResult::STATUS_SUCCESS, 'message', ['extra' => 'bla']);
        $results[] = $result;
        $result = new CheckResult('warning');
        $result->set(CheckResult::STATUS_WARNING, 'message', ['extra' => 'bla']);
        $results[] = $result;
        $result = new CheckResult('failure');
        $result->set(CheckResult::STATUS_FAILURE, 'message', ['extra' => 'bla']);
        $results[] = $result;

        return $results;
    }
}
