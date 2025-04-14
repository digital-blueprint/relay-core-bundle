<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DB;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MigratePostEvent extends Event
{
    public function __construct(private readonly OutputInterface $output)
    {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
