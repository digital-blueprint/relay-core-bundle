<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck;

interface CheckInterface
{
    public function getName(): string;

    /**
     * @return CheckResult[]
     */
    public function check(): array;
}
