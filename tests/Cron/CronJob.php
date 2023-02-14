<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Cron;

use Dbp\Relay\CoreBundle\Cron\CronJobInterface;
use Dbp\Relay\CoreBundle\Cron\CronOptions;

class CronJob implements CronJobInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $interval;

    /**
     * @var bool
     */
    public $ran;

    public function __construct(string $interval, string $name = '')
    {
        $this->name = $name;
        $this->interval = $interval;
        $this->ran = false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function run(CronOptions $options): void
    {
        $this->ran = true;
    }
}
