<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Service;

use Dbp\Relay\CoreBundle\Doctrine\QueryHelper;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class TestResourceService
{
    public function __construct(private readonly ?EntityManagerInterface $entityManager = null)
    {
    }

    public function addTestResource(TestResource $testResource): TestResource
    {
        $testResource->setIdentifier((string) Uuid::v4());
        QueryHelper::saveEntity($testResource, $this->entityManager);

        return $testResource;
    }

    public function getTestResource(string $identifier): ?TestResource
    {
        return QueryHelper::tryGetEntityById($identifier, TestResource::class, $this->entityManager);
    }

    public function getTestResources(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return QueryHelper::getEntities(TestResource::class, $this->entityManager,
            $currentPageNumber, $maxNumItemsPerPage, Options::getFilter($options));
    }

    public function removeTestResource(TestResource $testResource): void
    {
        QueryHelper::removeEntity($testResource, $this->entityManager);
    }

    public function addTestSubResource(TestSubResource $testSubResource): TestSubResource
    {
        $testSubResource->setIdentifier((string) Uuid::v4());
        QueryHelper::saveEntity($testSubResource, $this->entityManager);

        return $testSubResource;
    }

    public function removeTestSubResource(TestSubResource $testSubResource): void
    {
        QueryHelper::removeEntity($testSubResource, $this->entityManager);
    }

    public function getTestSubResource(string $identifier): ?TestSubResource
    {
        return QueryHelper::tryGetEntityById($identifier, TestSubResource::class, $this->entityManager);
    }

    public function getTestSubResources(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return QueryHelper::getEntities(TestSubResource::class, $this->entityManager,
            $currentPageNumber, $maxNumItemsPerPage, Options::getFilter($options));
    }
}
