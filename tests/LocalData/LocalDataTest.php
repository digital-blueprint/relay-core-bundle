<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
use Dbp\Relay\CoreBundle\Tests\Rest\TestDataProvider;
use Dbp\Relay\CoreBundle\Tests\Rest\TestEntity;
use Dbp\Relay\CoreBundle\TestUtils\DataProviderTester;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

class LocalDataTest extends TestCase
{
    private ?TestDataProvider $testDataProvider = null;
    private ?DataProviderTester $dataProviderTester = null;

    protected function setUp(): void
    {
        parent::setUp();

        $localDataEventSubscriber = new TestEntityLocalDataEventSubscriber('TestEntity');
        $localDataEventSubscriber->setConfig(self::createSubscriberConfig());

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($localDataEventSubscriber);

        $this->testDataProvider = new TestDataProvider($eventDispatcher);
        $this->testDataProvider->setConfig(self::createTestDataProviderConfig());

        $this->dataProviderTester = DataProviderTester::create($this->testDataProvider,
            TestEntity::class, [['TestEntity:output', 'LocalData:output']]);

        DataProviderTester::login($this->testDataProvider,
            TestDataProvider::TEST_USER_IDENTIFIER, [
                'IS_USER' => true,
                'IS_ADMIN' => false,
            ]);
    }

    public function testLocalDataUndefined()
    {
        try {
            $this->getTestEntityWithLocalData('foo', []);
            $this->fail('Expected ApiError exception not thrown.');
        } catch (ApiError $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
        }
    }

    public function testLocalDataDefinedButForOtherEntity()
    {
        try {
            $this->getTestEntityWithLocalData('other_entity_attribute', []);
            $this->fail('Expected ApiError exception not thrown.');
        } catch (ApiError $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
        }
    }

    public function testLocalDataMappingDefinedButForOtherEntity()
    {
        try {
            $this->getTestEntityWithLocalData('attribute_4', ['src_attribute_4' => 'value_4']);
            $this->fail('Expected ApiError exception not thrown.');
        } catch (ApiError $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
        }
    }

    public function testScalarLocalDataMappingWithScalarSourceValue()
    {
        // scalar attribute, scalar source attribute  -> return scalar source attribute value
        $localDataAttributeName = 'attribute_1';
        $sourceData = ['src_attribute_1' => 'value_1'];
        $this->testDataProvider->setSourceData($sourceData);
        $testEntity = $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testScalarLocalDataMappingWithArraySourceValue()
    {
        // scalar attribute, array source attribute -> return scalar source attribute value (i.e. first array element)
        $localDataAttributeName = 'attribute_1';
        $sourceData = ['src_attribute_1' => ['value_1']];
        $this->testDataProvider->setSourceData($sourceData);
        $testEntity = $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testArrayLocalDataMappingWithArraySourceValue()
    {
        // array attribute, array source attribute -> return array source attribute value
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = ['array_src_attribute_1' => ['value_1']];
        $this->testDataProvider->setSourceData($sourceData);
        $testEntity = $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
        $this->assertEquals(['value_1'], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testArrayLocalDataMappingWithScalarSourceValue()
    {
        // array attribute, array source attribute -> return array with scalar source value as only element
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = ['array_src_attribute_1' => 'value_1'];
        $this->testDataProvider->setSourceData($sourceData);
        $testEntity = $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
        $this->assertEquals(['value_1'], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultValue()
    {
        // source data attribute of non-array attribute not available -> local data attribute of entity must be null
        $localDataAttributeName = 'attribute_1';
        $sourceData = [];
        $this->testDataProvider->setSourceData($sourceData);
        $testEntity = $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
        $this->assertNull($testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultArrayValue()
    {
        // source data attribute of array type attribute not available -> local data attribute of entity must be null
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = [];
        $this->testDataProvider->setSourceData($sourceData);
        $testEntity = $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
        $this->assertNull($testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataAttributeUnauthorized()
    {
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];
        $this->testDataProvider->setSourceData($sourceData);
        try {
            $this->getTestEntityWithLocalData($localDataAttributeName, $sourceData);
            $this->fail('Expected ApiError exception not thrown.');
        } catch (ApiError $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getStatusCode());
        }
    }

    public function testLocalDataAttributeUnauthorizedCollection()
    {
        // authorization expression of attribute evaluates to false -> local data attribute of all entities must be null
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        $this->testDataProvider->setSourceData($sourceData);
        try {
            $this->getTestEntitiesWithLocalData($localDataAttributeName, [
                '0' => $sourceData,
                '1' => $sourceData,
                '2' => $sourceData,
            ]);
            $this->fail('Expected ApiError exception not thrown.');
        } catch (ApiError $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getStatusCode());
        }
    }

    /**
     * @throws FilterException
     */
    public function testLocalDataPreEvent(): void
    {
        $queryParameters = [];
        parse_str('filter[localData.attribute_1]="value_1"', $queryParameters);

        // assert that the local data attribute path is internally mapped to the source attribute path in the filter
        $this->testDataProvider->setSourceData([[]]);
        $this->dataProviderTester->getPage(filters: $queryParameters);
        $filter = Options::getFilter($this->testDataProvider->getOptions());

        $expectedFilter = FilterTreeBuilder::create()->equals('src_attribute_1', 'value_1')->createFilter();

        $this->assertEquals($expectedFilter->toArray(), $filter->toArray());
    }

    private function getTestEntityWithLocalData(string $includeLocal, array $sourceData): ?TestEntity
    {
        $filters = [
            'includeLocal' => $includeLocal,
        ];
        $this->testDataProvider->setSourceData(['id' => $sourceData]);

        return $this->dataProviderTester->getItem('id', $filters);
    }

    private function getTestEntitiesWithLocalData(string $includeLocal, array $sourceData, array $filterQueryParameters = []): array
    {
        $filters = [
            'includeLocal' => $includeLocal,
        ];
        $filters = array_merge($filters, $filterQueryParameters);
        $this->testDataProvider->setSourceData($sourceData);

        return $this->dataProviderTester->getPage(filters: $filters);
    }

    private static function createTestDataProviderConfig(): array
    {
        $config = [];
        $config['rest']['query']['filter']['enable_query_filters'] = true;
        $config['local_data'] = [
            [
                'local_data_attribute' => 'attribute_1',
                'read_policy' => 'true',
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'read_policy' => 'true',
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'read_policy' => 'false',
                // no entity_short_name defined here, so applies to all entities
            ],
            [
                'local_data_attribute' => 'attribute_4',
                'read_policy' => 'true',
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'read_policy' => 'true',
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'other_entity_attribute',
                'entity_short_name' => 'OtherTestEntity',
                'read_policy' => 'true',
            ],
        ];

        return $config;
    }

    private static function createSubscriberConfig(): array
    {
        $config = [];
        $config['local_data_mapping'] = [
            [
                'local_data_attribute' => 'attribute_1',
                'source_attribute' => 'src_attribute_1',
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'source_attribute' => 'src_attribute_2',
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'source_attribute' => 'src_attribute_3',
                // no entity_short_name defined here, so applies to all entities
            ],
            [
                'local_data_attribute' => 'attribute_4',
                'source_attribute' => 'src_attribute_4',
                'entity_short_name' => 'OtherTestEntity',
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'source_attribute' => 'array_src_attribute_1',
                'is_array' => true,
                'entity_short_name' => 'TestEntity',
            ],
            [
                'local_data_attribute' => 'other_entity_attribute',
                'source_attribute' => 'other_entity_src_attribute',
                'entity_short_name' => 'OtherTestEntity',
            ],
        ];

        return $config;
    }
}
