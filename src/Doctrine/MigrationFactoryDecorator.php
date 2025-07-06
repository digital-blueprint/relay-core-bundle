<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        private ContainerInterface $container)
    {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->migrationFactory->createVersion($migrationClassName);

        if ($migration instanceof AbstractEntityManagerMigration) {
            $migration->setContainer($this->container);
        }

        return $migration;
    }
}
