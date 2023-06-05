<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\LocalData\LocalDataEventDispatcher;
use Dbp\Relay\CoreBundle\LocalData\TestLocalDataAuthorizationService;
use Dbp\Relay\CoreBundle\Query\Operator;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

class LocalDataTest extends TestCase
{
    /** @var TestLocalDataAuthorizationService */
    private $testLocalDataAuthorizationService;

    /** @var LocalDataEventDispatcher */
    private $localDataEventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $localDataEventSubscriber = new TestEntityLocalDataEventSubscriber();
        $localDataEventSubscriber->setConfig(self::createSubscriberConfig());

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($localDataEventSubscriber);

        $this->localDataEventDispatcher = new LocalDataEventDispatcher(TestEntity::class, $eventDispatcher);

        $this->testLocalDataAuthorizationService = new TestLocalDataAuthorizationService();
        $this->testLocalDataAuthorizationService->__injectServices(
            new TestUserSession('testuser'),
            new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([]), new EventDispatcher()));
        $this->testLocalDataAuthorizationService->setConfig(self::createAuthzConfig());
    }

    public function testScalarLocalDataMappingWithScalarSourceValue()
    {
        // scalar attribute, scalar source attribute  -> return scalar source attribute value
        $localDataAttributeName = 'attribute_1';
        $sourceData = ['src_attribute_1' => 'value_1'];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testScalarLocalDataMappingWithArraySourceValue()
    {
        // scalar attribute, array source attribute -> return scalar source attribute value (i.e. first array element)
        $localDataAttributeName = 'attribute_1';
        $sourceData = ['src_attribute_1' => ['value_1']];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testArrayLocalDataMappingWithArraySourceValue()
    {
        // array attribute, array source attribute -> return array source attribute value
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = ['array_src_attribute_1' => ['value_1']];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals(['value_1'], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testArrayLocalDataMappingWithScalarSourceValue()
    {
        // array attribute, array source attribute -> return array with scalar source value as only element
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = ['array_src_attribute_1' => 'value_1'];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals(['value_1'], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultValue()
    {
        // source data attribute of non-array attribute not available -> local data attribute of entity must be null
        $localDataAttributeName = 'attribute_1';
        $sourceData = [];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertNull($testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultArrayValue()
    {
        // source data attribute of array type attribute not available -> local data attribute of entity must be null
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = [];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertNull($testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataAttributeUnauthorized()
    {
        // authorization expression of attribute evaluates to false -> local data attribute of entity must be null
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        $entity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertNull($entity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataAttributeUnauthorizedCollection()
    {
        // authorization expression of attribute evaluates to false -> local data attribute of all entities must be null
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        $entities = $this->getTestEntities($localDataAttributeName, $sourceData, null, 3);
        $this->assertNull($entities[0]->getLocalDataValue($localDataAttributeName));
        $this->assertNull($entities[1]->getLocalDataValue($localDataAttributeName));
        $this->assertNull($entities[2]->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataQuery()
    {
        // 'attribute_1' has a configured source attribute 'src_attribute_1'.
        // Post-condition: options contain the mapped attribute 'src_attribute_1' as a key  with the given value 'value_1'.
        $localDataAttributeName = 'attribute_1';
        $queryLocal = $localDataAttributeName.':value_1';
        $options = $this->createAndValidateOptions($localDataAttributeName, $queryLocal);

        $this->localDataEventDispatcher->onNewOperation($options);
        $preEvent = new TestEntityPreEvent($options);
        $this->localDataEventDispatcher->dispatch($preEvent);

        $localQueryParameter = $preEvent->getOptions()[0];
        $this->assertEquals('value_1', $localQueryParameter->getValue());
        $this->assertEquals('src_attribute_1', $localQueryParameter->getField());
        $this->assertEquals(Operator::ICONTAINS, $localQueryParameter->getOperator());
    }

    public function testLocalDataQueryAttributeUnacknowledgedNotConfigured()
    {
        // Throw bad request error since '404' is not a configured local data attribute.
        $localDataAttributeName = '404';
        $queryLocal = $localDataAttributeName.':no_value';
        try {
            $this->createAndValidateOptions($localDataAttributeName, $queryLocal);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }
    }

    public function testLocalDataQueryAttributeUnacknowledgedNotQueryable()
    {
        // Throw bad request error since 'attribute_2' is configured 'allow_query': false
        $localDataAttributeName = 'attribute_2';
        $queryLocal = $localDataAttributeName.':value_2';

        try {
            $this->createAndValidateOptions($localDataAttributeName, $queryLocal);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }
    }

    public function testLocalDataQueryUnauthorized()
    {
        // authorization expression of local data attribute 'attribute_3' evaluates to false -> remove entity from result set
        $localDataAttributeName = 'attribute_3';
        $localQuery = $localDataAttributeName.':value_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        self::createAndValidateOptions($localDataAttributeName, $localQuery);

        $entities = $this->getTestEntities($localDataAttributeName, $sourceData, $localQuery);
        $this->assertEmpty($entities);
    }

    public function testLocalDataQueryUnauthorizedCollection()
    {
        // authorization expression of local data attribute 'attribute_3' evaluates to false -> remove entities from result set
        $localDataAttributeName = 'attribute_3';
        $localQuery = $localDataAttributeName.':value_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        self::createAndValidateOptions($localDataAttributeName, $localQuery);

        $entities = $this->getTestEntities($localDataAttributeName, $sourceData, $localQuery, 3);
        $this->assertEmpty($entities);
    }

    public function testMappingLocalQueryParameterValue()
    {
        // a query value mapping expression is defined for 'attribute_4' ("value + 1"). assert that the query value is incremented by 1.
        $localDataAttributeName = 'attribute_4';
        $localQuery = $localDataAttributeName.':4';

        $options = $this->createAndValidateOptions($localDataAttributeName, $localQuery);

        $this->localDataEventDispatcher->onNewOperation($options);
        $preEvent = new TestEntityPreEvent($options);
        $this->localDataEventDispatcher->dispatch($preEvent);

        $localQueryParameter = $preEvent->getOptions()[0];
        $this->assertEquals('5', $localQueryParameter->getValue());
        $this->assertEquals('src_attribute_4', $localQueryParameter->getField());
        $this->assertEquals(Operator::ICONTAINS, $localQueryParameter->getOperator());
    }

    public function testMappingSourceDataValue()
    {
        // a value mapping expression is defined for 'attribute_4' ("value + 1"). assert that the source value is incremented by 1.
        $localDataAttributeName = 'attribute_4';
        $sourceData = ['src_attribute_4' => '4'];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals('5', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    private function createAndValidateOptions(string $includeLocal, string $queryLocal): array
    {
        $filters = [LocalData::INCLUDE_PARAMETER_NAME => $includeLocal, LocalData::QUERY_PARAMETER_NAME => $queryLocal];

        $options = [];
        LocalData::addOptions($options, $filters);

        $this->testLocalDataAuthorizationService->checkRequestedLocalDataAttributes($options);

        return $options;
    }

    private static function createAuthzConfig(): array
    {
        $config = [];
        $config['local_data'] = [
            [
                'local_data_attribute' => 'attribute_1',
                'read_policy' => 'true',
                'allow_query' => true,
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'read_policy' => 'true',
                'allow_query' => false,
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'read_policy' => 'false',
                'allow_query' => true,
            ],
            [
                'local_data_attribute' => 'attribute_4',
                'read_policy' => 'true',
                'allow_query' => true,
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'read_policy' => 'true',
                'allow_query' => false,
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
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'source_attribute' => 'src_attribute_2_1',
                'allow_query' => false,
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'source_attribute' => 'src_attribute_3',
                'allow_query' => true,
            ],
            [
                'local_data_attribute' => 'attribute_4',
                'source_attribute' => 'src_attribute_4',
                'map_value' => 'value + 1',
                'map_filters' => 'relay.map(filters, "Filter.create(value.getValue() + 1)")',
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'source_attribute' => 'array_src_attribute_1',
                'is_array' => true,
            ],
        ];

        return $config;
    }

    private function getTestEntities(string $includeLocal, array $sourceData, string $queryLocal = null, int $numEntities = 1): array
    {
        $testEntities = [];
        for ($i = 0; $i <= $numEntities; ++$i) {
            $testEntities[] = new TestEntity();
        }

        $filters = [];
        $filters[LocalData::INCLUDE_PARAMETER_NAME] = $includeLocal;
        if ($queryLocal) {
            $filters[LocalData::QUERY_PARAMETER_NAME] = $queryLocal;
        }

        $options = [];
        LocalData::addOptions($options, $filters);

        $this->testLocalDataAuthorizationService->checkRequestedLocalDataAttributes($options);

        $this->localDataEventDispatcher->onNewOperation($options);

        foreach ($testEntities as $testEntity) {
            $this->localDataEventDispatcher->dispatch(new TestEntityPostEvent($testEntity, $sourceData));
        }

        return $this->testLocalDataAuthorizationService->enforceLocalDataAccessControlPolicies($testEntities, $options);
    }
}
