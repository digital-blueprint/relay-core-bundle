<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AbstractDataProviderTest extends TestCase
{
    /** @var TestDataProvider */
    private $testDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDataProvider = new TestDataProvider(new EventDispatcher());
    }

    public function testGetEntity()
    {
        $testEntity = self::getTestEntity($this->testDataProvider);

        $this->assertInstanceOf(TestEntity::class, $testEntity);
    }

    public function testGetEmptyPage()
    {
        $testEntities = self::getTestEntities($this->testDataProvider);

        $this->assertEmpty($testEntities);
    }

    public function testPaginationParameters()
    {
        $currentPage = 2;
        $itemsPerPage = 3;
        $filters = [
            'page' => strval($currentPage),
            'perPage' => strval($itemsPerPage),
        ];

        // must contain the page range for this test to work
        $sourceData = [
            [], [], [], [], [], [], [], [], [], [], [], [],
        ];

        $paginator = self::getTestEntityPaginator($this->testDataProvider, $filters, $sourceData);

        $this->assertEquals($itemsPerPage, $paginator->getItemsPerPage());
        $this->assertEquals($currentPage, $paginator->getCurrentPage());
        $this->assertCount($itemsPerPage, $paginator->getItems());
    }

    public static function getTestEntity(TestDataProvider $testDataProvider, array $filters = [], array $sourceData = []): TestEntity
    {
        $testDataProvider->setSourceData($sourceData);

        /** @var TestEntity */
        return $testDataProvider->provide(new Get(), ['identifier' => '0'], ['filters' => $filters]);
    }

    public static function getTestEntities(TestDataProvider $testDataProvider, array $filters = [], array $sourceData = []): array
    {
        return self::getTestEntityPaginator($testDataProvider, $filters, $sourceData)->getItems();
    }

    private static function getTestEntityPaginator(TestDataProvider $testDataProvider, array $filters = [], array $sourceData = []): PartialPaginator
    {
        $testDataProvider->setSourceData($sourceData);

        /** @var PartialPaginator */
        return $testDataProvider->provide(new GetCollection(), [], ['filters' => $filters]);
    }
}
