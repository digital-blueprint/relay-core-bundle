<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\LocalData\LocalDataEventDispatcher;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

class LocalDataTest extends TestCase
{
    /** @var LocalDataEventDispatcher */
    private $localDataEventDispatcher;

    /** @var TestEntityLocalDataEventSubscriber */
    private $localDataEventSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $localDataEventSubscriber = new TestEntityLocalDataEventSubscriber();
        $localDataEventSubscriber->_injectServices(new TestUserSession('testuser'), new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([]), new EventDispatcher()));
        $localDataEventSubscriber->setConfig(self::createConfig());

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($localDataEventSubscriber);

        // explicitly specify the entities unique name since it doesn't have an @ApiResource annotation
        $this->localDataEventDispatcher = new LocalDataEventDispatcher(TestEntity::class, $eventDispatcher, TestEntity::class);
        $this->localDataEventSubscriber = $localDataEventSubscriber;
    }

    public function testLocalDataMapping()
    {
        // source attribute specified in config is present in source data -> return source attribute value
        $localDataAttributeName = 'attribute_1';
        $testEntity = $this->getTestEntity($localDataAttributeName, ['src_attribute_1' => 'value_1']);
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultValue()
    {
        // default value specified in config -> return default value
        $localDataAttributeName = 'attribute_1';
        $testEntity = $this->getTestEntity($localDataAttributeName, []);
        $this->assertEquals(0, $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingNoneOfSourceAttributesFoundError()
    {
        // no default value specified in config -> throw exception on none of source attributes found
        $localDataAttributeName = 'attribute_2';
        try {
            $this->getTestEntity($localDataAttributeName, []);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
        }
    }

    public function testLocalDataMappingDefaultArrayValue()
    {
        // default array value specified in config -> return default array value
        $localDataAttributeName = 'array_attribute_1';
        $testEntity = $this->getTestEntity($localDataAttributeName, []);
        $this->assertEquals([0], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingFallback()
    {
        // first source attribute specified in config is present in source data -> return first source attribute value
        $localDataAttributeName = 'attribute_2';
        $testEntity = $this->getTestEntity($localDataAttributeName, ['src_attribute_2_1' => 'value_2_1', 'src_attribute_2_2' => 'value_2_2']);
        $this->assertEquals('value_2_1', $testEntity->getLocalDataValue($localDataAttributeName));

        // first source attribute specified in config is not present in source data, however second attribute is preset -> return second source attribute value
        $testEntity = $this->getTestEntity($localDataAttributeName, ['src_attribute_2_2' => 'value_2_2']);
        $this->assertEquals('value_2_2', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingAccessDenied()
    {
        // authorization expression of attribute evaluates to false -> deny access
        $localDataAttributeName = 'attribute_3';
        try {
            $this->getTestEntity($localDataAttributeName, ['src_attribute_2_1' => 'value_2_1', 'src_attribute_2_2' => 'value_2_2']);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());
        }
    }

    public function testLocalDataQuery()
    {
        // 'attribute_1' has a configured source attribute 'src_attribute_1'.
        // Post-condition: options contain the mapped attribute 'src_attribute_1' as a key  with the given value 'value_1'.
        $localDataAttributeName = 'attribute_1';

        $options = [];
        $options[LocalData::QUERY_PARAMETER_NAME] = $localDataAttributeName.':value_1';

        $this->localDataEventDispatcher->onNewOperation($options);
        $preEvent = new TestEntityPreEvent();
        $this->localDataEventDispatcher->dispatch($preEvent);

        $filters = $preEvent->getQueryParametersOut();
        $this->assertArrayHasKey('src_attribute_1', $filters);
        $this->assertEquals('value_1', $filters['src_attribute_1']);
    }

    public function testLocalDataQueryAttributeUnacknowledged()
    {
        // 'attribute_4' has no configured source attribute.
        // Throw bad request error because no event subscriber acknowledged local query parameter 'attribute_4'.
        $localDataAttributeName = 'attribute_4';

        $options = [];
        $options[LocalData::QUERY_PARAMETER_NAME] = $localDataAttributeName.':value_4';

        try {
            $this->localDataEventDispatcher->onNewOperation($options);
            $preEvent = new TestEntityPreEvent();
            $this->localDataEventDispatcher->dispatch($preEvent);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }
    }

    public function testLocalDataQueryAccessDenied()
    {
        // authorization expression of attribute evaluates to false -> deny access
        $localDataAttributeName = 'attribute_3';

        $options = [];
        $options[LocalData::QUERY_PARAMETER_NAME] = $localDataAttributeName.':value_1';

        try {
            $this->localDataEventDispatcher->onNewOperation($options);
            $preEvent = new TestEntityPreEvent();
            $this->localDataEventDispatcher->dispatch($preEvent);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());
        }
    }

    private function getTestEntity(string $includeLocal, array $sourceData): TestEntity
    {
        $testEntity = new TestEntity();

        $options = [];
        $options[LocalData::INCLUDE_PARAMETER_NAME] = $includeLocal;

        $this->localDataEventDispatcher->onNewOperation($options);
        $this->localDataEventDispatcher->dispatch(new TestEntityPostEvent($testEntity, $sourceData));

        return $testEntity;
    }

    private static function createConfig(): array
    {
        $config = [];
        $config['local_data_mapping'] = [
            [
                'local_data_attribute' => 'attribute_1',
                'source_attributes' => ['src_attribute_1'],
                'authorization_expression' => 'true',
                'default_value' => 0,
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'source_attributes' => ['src_attribute_2_1', 'src_attribute_2_2'],
                'authorization_expression' => 'true',
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'source_attributes' => ['src_attribute_3'],
                'authorization_expression' => 'false',
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'source_attributes' => ['array_src_attribute_1'],
                'authorization_expression' => 'true',
                'default_values' => [0],
            ],
        ];

        return $config;
    }
}
