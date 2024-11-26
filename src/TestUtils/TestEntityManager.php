<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestEntityManager
{
    public function __construct(
        protected readonly EntityManager $entityManager,
        protected readonly ?string $entityClassName = null)
    {
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

    public static function create(ContainerInterface $container, string $entityManagerId, ?string $entityClassName = null): self
    {
        return new self(self::setUpEntityManager($container, $entityManagerId), $entityClassName);
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function saveEntity(object $entity): void
    {
        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }

    public function removeEntity(object $entity): void
    {
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
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
            return $this->entityManager->getRepository($entityClassName)
                ->findOneBy(['identifier' => $identifier]);
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
        $ENTITY_ALIAS = 'e';

        $entityClassName ??= $this->entityClassName;
        if ($entityClassName === null) {
            throw new \RuntimeException('please specify the class of the entity to get');
        }

        try {
            return $this->entityManager->createQueryBuilder()
                ->select($ENTITY_ALIAS)
                ->from($entityClassName, $ENTITY_ALIAS)
                ->getQuery()
                ->setFirstResult(Pagination::getFirstItemIndex($currentPageNumber, $maxNumItemsPerPage))
                ->setMaxResults($maxNumItemsPerPage)
                ->getResult();
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }
}
