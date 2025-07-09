<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractEntityManagerMigration extends AbstractMigration
{
    protected ?ContainerInterface $container = null;

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function preUp(Schema $schema): void
    {
        $this->skipInvalidDB();
    }

    public function preDown(Schema $schema): void
    {
        $this->skipInvalidDB();
    }

    protected function getEntityManager(): EntityManager
    {
        $entityManagerId = $this->getEntityManagerId();

        return $this->container->get("doctrine.orm.{$entityManagerId}_entity_manager");
    }

    abstract protected function getEntityManagerId(): string;

    private function skipInvalidDB(): void
    {
        $entityManagerId = $this->getEntityManagerId();
        $this->skipIf($this->connection !== $this->getEntityManager()->getConnection(),
            "Migration can't be executed on this connection, use --em={$entityManagerId} to select the right one.'");
    }
}
