<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractEntityManagerMigration extends AbstractMigration
{
    private ?ContainerInterface $container = null;

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

    private function getEntityManager(): EntityManager
    {
        $entityManagerId = $this->getEntityManagerId();

        return $this->container->get("doctrine.orm.{$entityManagerId}_entity_manager");
    }

    private function skipInvalidDB(): void
    {
        $entityManagerId = $this->getEntityManagerId();
        if ($entityManagerId === 'dbp_relay_base_room_connector_campusonline_bundle'
            || $this->connection !== $this->getEntityManager()->getConnection()) {
            dump(spl_object_id($this->connection), spl_object_id($this->getEntityManager()->getConnection()));
        }
        $this->skipIf($this->connection !== $this->getEntityManager()->getConnection(),
            "Migration can't be executed on this connection, use --em={$entityManagerId} to select the right one.'");
    }

    abstract protected function getEntityManagerId(): string;
}
