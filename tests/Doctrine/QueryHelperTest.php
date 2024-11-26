<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Doctrine;

use Dbp\Relay\CoreBundle\Doctrine\QueryHelper;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Tests\Kernel;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\TestUtils\TestEntityManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryHelperTest extends KernelTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = TestEntityManager::setUpEntityManager(self::bootKernel()->getContainer(),
            Kernel::TEST_ENTITY_MANAGER_ID);
    }

    public function testSaveEntity(): void
    {
        $testResource = TestResource::createTestResource();
        QueryHelper::saveEntity($testResource, $this->entityManager);

        $testResourceFromDb = $this->entityManager->getRepository(TestResource::class)
            ->findOneBy(['identifier' => $testResource->getIdentifier()]);
        $this->assertNotNull($testResourceFromDb);
        $this->assertEquals($testResource->getIdentifier(), $testResourceFromDb->getIdentifier());
    }

    public function testRemoveEntity(): void
    {
        $testResource = TestResource::createTestResource();
        $this->entityManager->persist($testResource);
        $this->entityManager->flush();
        $testResourceFromDb = $this->entityManager->getRepository(TestResource::class)
            ->findOneBy(['identifier' => $testResource->getIdentifier()]);
        $this->assertNotNull($testResourceFromDb);

        QueryHelper::removeEntity($testResource, $this->entityManager);
        $testResourceFromDb = $this->entityManager->getRepository(TestResource::class)
            ->findOneBy(['identifier' => $testResource->getIdentifier()]);
        $this->assertNull($testResourceFromDb);
    }

    public function testTryGetEntity(): void
    {
        $testResourceFromGet = QueryHelper::tryGetEntityById('404', TestResource::class, $this->entityManager);
        $this->assertNull($testResourceFromGet);

        $testResource = TestResource::createTestResource();
        $this->entityManager->persist($testResource);
        $this->entityManager->flush();

        $testResourceFromGet = QueryHelper::tryGetEntityById(
            $testResource->getIdentifier(), TestResource::class, $this->entityManager);
        $this->assertNotNull($testResourceFromGet);
        $this->assertEquals($testResource->getIdentifier(), $testResourceFromGet->getIdentifier());
    }

    public function testGetEntities(): void
    {
        $testResource1 = TestResource::createTestResource();
        $testResource2 = TestResource::createTestResource();
        $testResource3 = TestResource::createTestResource();

        $testResource1->setContent('test resource 1');
        $testResource2->setContent('test resource 2');
        $testResource3->setContent('test resource 3');

        $this->entityManager->persist($testResource1);
        $this->entityManager->persist($testResource2);
        $this->entityManager->persist($testResource3);
        $this->entityManager->flush();

        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager);
        $this->assertCount(3, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $testResourcePage1 = QueryHelper::getEntities(TestResource::class, $this->entityManager, 1, 2);
        $this->assertCount(2, $testResourcePage1);
        $testResourcePage2 = QueryHelper::getEntities(TestResource::class, $this->entityManager, 2, 2);
        $this->assertCount(1, $testResourcePage2);
        $testResources = array_merge($testResourcePage1, $testResourcePage2);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);
    }

    public function testGetEntitiesWithFilter(): void
    {
        $testResource1 = TestResource::createTestResource();
        $testResource2 = TestResource::createTestResource();
        $testResource3 = TestResource::createTestResource();

        $testResource1->setContent('foo bar baz');
        $testResource1->setNumber(1);
        $testResource2->setContent('bar baz foo');
        $testResource2->setNumber(2);
        $testResource3->setContent('baz foo bar');
        $testResource3->setNumber(3);
        $testResource3->setSecret(null);

        $this->entityManager->persist($testResource1);
        $this->entityManager->persist($testResource2);
        $this->entityManager->persist($testResource3);
        $this->entityManager->flush();

        $filter = FilterTreeBuilder::create()
            ->iContains('content', 'bar')
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(3, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->iStartsWith('content', 'bar')
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);

        $filter = FilterTreeBuilder::create()
            ->iEndsWith('content', 'bar')
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->iEndsWith('content', 'bar')
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->equals('number', 3)
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->greaterThanOrEqual('number', 2)
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->lessThanOrEqual('number', 2)
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);

        $filter = FilterTreeBuilder::create()
            ->isNull('secret')
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->inArray('number', [1, 2])
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);

        // test pagination with filter:
        $testResourcePage1 = QueryHelper::getEntities(
            TestResource::class, $this->entityManager, 1, 1, filter: $filter);
        $this->assertCount(1, $testResourcePage1);
        $testResourcePage2 = QueryHelper::getEntities(
            TestResource::class, $this->entityManager, 2, 1, filter: $filter);
        $this->assertCount(1, $testResourcePage2);

        $testResources = array_merge($testResourcePage1, $testResourcePage2);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
    }

    public function testGetEntitiesWithLogicalClauses(): void
    {
        $testResource1 = TestResource::createTestResource();
        $testResource2 = TestResource::createTestResource();
        $testResource3 = TestResource::createTestResource();

        $testResource1->setContent('foo bar baz');
        $testResource1->setNumber(1);
        $testResource2->setContent('bar baz foo');
        $testResource2->setNumber(2);
        $testResource3->setContent('baz foo bar');
        $testResource3->setNumber(3);
        $testResource3->setSecret(null);

        $this->entityManager->persist($testResource1);
        $this->entityManager->persist($testResource2);
        $this->entityManager->persist($testResource3);
        $this->entityManager->flush();

        // there is an implicit AND root node
        $filter = FilterTreeBuilder::create()
            ->iContains('content', 'r b')
            ->equals('number', 2)
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);

        $filter = FilterTreeBuilder::create()
            ->or()
               ->iContains('content', 'r b')
               ->isNull('secret')
            ->end()
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(3, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->or()
               ->and()
                  ->iContains('content', 'r b')
                  ->equals('number', 2)
               ->end()
               ->and()
                   ->isNull('secret')
               ->end()
            ->end()
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->and()
                ->or()
                    ->iContains('content', 'r b')
                    ->equals('number', 2)
                ->end()
                ->iStartsWith('content', 'foo')
            ->end()
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource1, $testResources);

        $filter = FilterTreeBuilder::create()
            ->not()
            ->iStartsWith('content', 'foo')
            ->end()
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->or()
                ->not()
                   ->lessThanOrEqual('number', 2)
                ->end()
                ->iStartsWith('content', 'foo')
            ->end()
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(2, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->and()
                ->iContains('content', 'r b')
                ->equals('number', 2)
                ->iStartsWith('content', 'baz')
            ->end()
            ->createFilter();
        $testResources = QueryHelper::getEntities(TestResource::class, $this->entityManager, filter: $filter);
        $this->assertCount(0, $testResources);
    }

    public function testAddFilter(): void
    {
        $testResource1 = TestResource::createTestResource();
        $testResource2 = TestResource::createTestResource();
        $testResource3 = TestResource::createTestResource();

        $testResource1->setContent('foo bar baz');
        $testResource1->setNumber(1);
        $testResource2->setContent('bar baz foo');
        $testResource2->setNumber(2);
        $testResource3->setContent('baz foo bar');
        $testResource3->setNumber(3);
        $testResource3->setSecret(null);

        $this->entityManager->persist($testResource1);
        $this->entityManager->persist($testResource2);
        $this->entityManager->persist($testResource3);
        $this->entityManager->flush();

        $ENTITY_ALIAS = 'e';
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select($ENTITY_ALIAS)
            ->from(TestResource::class, $ENTITY_ALIAS);

        $testResources = $queryBuilder
            ->getQuery()
            ->getResult();
        $this->assertCount(3, $testResources);
        $this->assertContainsResource($testResource1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
        $this->assertContainsResource($testResource3, $testResources);

        $filter = FilterTreeBuilder::create()
            ->iContains('content', 'r b')
            ->equals('number', 2)
            ->createFilter();
        QueryHelper::addFilter($queryBuilder, $ENTITY_ALIAS, $filter);
        $testResources = $queryBuilder
            ->getQuery()
            ->getResult();
        $this->assertCount(1, $testResources);
        $this->assertContainsResource($testResource2, $testResources);
    }

    protected function containsResource(array $resources, mixed $resource): bool
    {
        foreach ($resources as $res) {
            if ($resource->getIdentifier() === $res->getIdentifier()) {
                return true;
            }
        }

        return false;
    }

    protected function assertContainsResource(mixed $resource, array $resources): void
    {
        $this->assertTrue($this->containsResource($resources, $resource), 'resource array does not contain given resource');
    }
}
