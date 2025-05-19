<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Doctrine\QueryHelper;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestEntityManager
{
    protected readonly EntityManager $entityManager;
    protected readonly ?string $entityClassName;

    public function __construct(
        ContainerInterface $container, string $entityManagerId, ?string $entityClassName = null)
    {
        $this->entityManager = self::setUpEntityManager($container, $entityManagerId);
        $this->entityClassName = $entityClassName;
    }

    public static function setUpEntityManager(ContainerInterface $container, string $entityManagerId): EntityManager
    {
        try {
            $entityManager = $container->get('doctrine')->getManager($entityManagerId);
            assert($entityManager instanceof EntityManager);

            // enable foreign key and 'ON DELETE CASCADE' support
            $sqlStatement = $entityManager->getConnection()->prepare('PRAGMA foreign_keys = ON');
            $sqlStatement->executeQuery();

            $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->updateSchema($metaData);
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }

        return $entityManager;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function saveEntity(object $entity): void
    {
        try {
            QueryHelper::saveEntity($entity, $this->entityManager);
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    public function removeEntity(object $entity): void
    {
        try {
            QueryHelper::removeEntity($entity, $this->entityManager);
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    public function getEntityByIdentifier(string $identifier, ?string $entityClassName = null): ?object
    {
        $entityClassName ??= $this->entityClassName;
        if ($entityClassName === null) {
            throw new \RuntimeException('please specify the class of the entity to get');
        }

        try {
            return QueryHelper::tryGetEntityById($identifier, $entityClassName, $this->entityManager);
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    /**
     * @param Filter|null $filter Currently ignored. TODO: implement
     *
     * @return object[]
     */
    public function getEntities(int $currentPageNumber, int $maxNumItemsPerPage, ?Filter $filter = null,
        ?string $entityClassName = null): array
    {
        $entityClassName ??= $this->entityClassName;
        if ($entityClassName === null) {
            throw new \RuntimeException('please specify the class of the entity to get');
        }

        try {
            return QueryHelper::getEntities($entityClassName, $this->entityManager,
                $currentPageNumber, $maxNumItemsPerPage, $filter);
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }
}
