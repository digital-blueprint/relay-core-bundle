<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\LocalData\LocalDataEventDispatcher;
use Dbp\Relay\CoreBundle\LocalData\TestLocalDataAuthorizationService;
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
        $this->testLocalDataAuthorizationService->_injectServices(new TestUserSession('testuser'), new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([]), new EventDispatcher()));
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
        // default value specified in config -> return default value
        $localDataAttributeName = 'attribute_1';
        $sourceData = [];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals(0, $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingNoneOfSourceAttributesFoundError()
    {
        // no default value specified in config -> throw exception on none of source attributes found
        $localDataAttributeName = 'attribute_2';
        $sourceData = [];
        try {
            $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
        }
    }

    public function testLocalDataMappingDefaultArrayValue()
    {
        // default array value specified in config -> return default array value
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = [];
        $testEntity = $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        $this->assertEquals([0], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingAccessDenied()
    {
        // authorization expression of attribute evaluates to false -> deny access
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];
        try {
            $this->getTestEntities($localDataAttributeName, $sourceData)[0];
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        }
    }

    public function testLocalDataMappingAccessDeniedCollection()
    {
        // authorization expression of attribute evaluates to false -> deny access
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];
        try {
            $this->getTestEntities($localDataAttributeName, $sourceData, null, 3);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        }
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

        $options = $preEvent->getOptions();
        $this->assertArrayHasKey('src_attribute_1', $options);
        $this->assertEquals('value_1', $options['src_attribute_1']);
    }

    public function testLocalDataQueryAttributeUnacknowledgedNotConfigured()
    {
        // Throw bad request error since 'attribute_4' is not a configured local data attribute.
        $localDataAttributeName = 'attribute_4';
        $queryLocal = $localDataAttributeName.':value_4';
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

    public function testLocalDataQueryAccessDenied()
    {
        // authorization expression of local data attribute 'attribute_3' evaluates to false -> deny access
        $localDataAttributeName = 'attribute_3';
        $localQuery = $localDataAttributeName.':value_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        self::createAndValidateOptions($localDataAttributeName, $localQuery);

        try {
            $this->getTestEntities($localDataAttributeName, $sourceData, $localQuery);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        }
    }

    public function testLocalDataQueryAccessDeniedCollection()
    {
        // authorization expression of local data attribute 'attribute_3' evaluates to false -> deny access
        $localDataAttributeName = 'attribute_3';
        $localQuery = $localDataAttributeName.':value_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        self::createAndValidateOptions($localDataAttributeName, $localQuery);

        try {
            $this->getTestEntities($localDataAttributeName, $sourceData, $localQuery, 3);
        } catch (ApiError $exception) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        }
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
                'authorization_expression' => 'true',
                'allow_query' => true,
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'authorization_expression' => 'true',
                'allow_query' => false,
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'authorization_expression' => 'false',
                'allow_query' => true,
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'authorization_expression' => 'true',
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
                'default_value' => 0,
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
                'local_data_attribute' => 'array_attribute_1',
                'source_attribute' => 'array_src_attribute_1',
                'default_values' => [0],
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

        $this->testLocalDataAuthorizationService->denyLocalDataAccessUnlessGranted($testEntities, $options);

        return $testEntities;
    }
}
