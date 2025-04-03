<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Service;

use Dbp\Relay\CoreBundle\Doctrine\QueryHelper;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

readonly class TestResourceService
{
    public function __construct(private ?EntityManagerInterface $entityManager = null)
    {
    }

    public function addTestResource(TestResource $testResource): TestResource
    {
        $testResource->setIdentifier((string) Uuid::v4());
        QueryHelper::saveEntity($testResource, $this->entityManager);

        return $testResource;
    }

    public function getTestResource(string $identifier, array $filters = [], array $options = []): ?TestResource
    {
        try {
            $filter = FilterTreeBuilder::create()
                ->equals('identifier', $identifier)
                ->createFilter();
            if ($filterFromOptions = Options::getFilter($options)) {
                $filter->combineWith($filterFromOptions);
            }
        } catch (FilterException $filterException) {
            throw new \RuntimeException('failed to create filter: '.$filterException->getMessage());
        }

        $entityPage = QueryHelper::getEntities(TestResource::class, $this->entityManager,
            1, 1, $filter);

        return false === empty($entityPage) ? $entityPage[0] : null;
    }

    /**
     * @throws \Exception
     */
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
