<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestUtils;

use Dbp\Relay\CoreBundle\Tests\Rest\TestDataProvider;
use Dbp\Relay\CoreBundle\Tests\Rest\TestEntity;
use Dbp\Relay\CoreBundle\TestUtils\DataProviderTester;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DataProviderTesterTest extends TestCase
{
    private DataProviderTester $dataProviderTester;
    private TestDataProvider $testDataProvider;

    protected function setUp(): void
    {
        $this->testDataProvider = new TestDataProvider(new EventDispatcher());
        $this->dataProviderTester = DataProviderTester::create(
            $this->testDataProvider, TestEntity::class, [['TestEntity:output', 'LocalData:output']]);
        DataProviderTester::login($this->testDataProvider, currentUserAttributes: ['ROLE_ADMIN' => false]);
    }

    public function testGetItem(): void
    {
        $ID = 'id';
        $this->testDataProvider->setSourceData([$ID => []]);

        $this->assertEquals(new TestEntity($ID), $this->dataProviderTester->getItem($ID));
    }

    public function testGetItemNotFound(): void
    {
        $this->assertNull($this->dataProviderTester->getItem('404'));
    }

    public function testGetCollection(): void
    {
        $ID1 = 'id1';
        $ID2 = 'id2';
        $ID3 = 'id3';

        $this->testDataProvider->setSourceData([
            $ID1 => [],
            $ID2 => [],
            $ID3 => [],
        ]);

        $testEntities = $this->dataProviderTester->getCollection();
        $this->assertCount(3, $testEntities);
        $this->assertContainsEquals(new TestEntity($ID1), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID2), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID3), $testEntities);

        // test pagination:
        $testEntityPage1 = $this->dataProviderTester->getCollection([
            'page' => 1,
            'perPage' => 2,
        ]);
        $this->assertCount(2, $testEntityPage1);

        $testEntityPage2 = $this->dataProviderTester->getCollection([
            'page' => 2,
            'perPage' => 2,
        ]);
        $this->assertCount(1, $testEntityPage2);

        $testEntityPage3 = $this->dataProviderTester->getCollection([
            'page' => 3,
            'perPage' => 2,
        ]);
        $this->assertCount(0, $testEntityPage3);

        $testEntities = array_merge($testEntityPage1, $testEntityPage2);
        $this->assertContainsEquals(new TestEntity($ID1), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID2), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID3), $testEntities);
    }

    public function testGetPage(): void
    {
        $ID1 = 'id1';
        $ID2 = 'id2';
        $ID3 = 'id3';

        $this->testDataProvider->setSourceData([
            $ID1 => [],
            $ID2 => [],
            $ID3 => [],
        ]);

        $testEntities = $this->dataProviderTester->getPage(1, 10);
        $this->assertCount(3, $testEntities);
        $this->assertContainsEquals(new TestEntity($ID1), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID2), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID3), $testEntities);

        // test pagination:
        $testEntityPage1 = $this->dataProviderTester->getPage(1, 2);
        $this->assertCount(2, $testEntityPage1);

        $testEntityPage2 = $this->dataProviderTester->getPage(2, 2);
        $this->assertCount(1, $testEntityPage2);

        $testEntityPage3 = $this->dataProviderTester->getPage(3, 2);
        $this->assertCount(0, $testEntityPage3);

        $testEntities = array_merge($testEntityPage1, $testEntityPage2);
        $this->assertContainsEquals(new TestEntity($ID1), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID2), $testEntities);
        $this->assertContainsEquals(new TestEntity($ID3), $testEntities);
    }
}
