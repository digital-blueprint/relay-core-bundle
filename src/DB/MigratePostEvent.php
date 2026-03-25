<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DB;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MigratePostEvent extends Event
{
    /**
     * @param array<string, string[]> $executedMigrations Migrations executed during this run, keyed by entity manager name.
     *                                                    Each value is an array of migration version strings.
     */
    public function __construct(
        private readonly OutputInterface $output,
        private readonly array $executedMigrations = [],
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Returns the migrations that were executed during this run, grouped by entity manager name.
     *
     * @return array<string, string[]>
     */
    public function getExecutedMigrations(): array
    {
        return $this->executedMigrations;
    }
}
