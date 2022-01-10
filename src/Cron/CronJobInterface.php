<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

interface CronJobInterface
{
    public function getName(): string;

    public function getInterval(): string;

    public function run(CronOptions $options): void;
}
