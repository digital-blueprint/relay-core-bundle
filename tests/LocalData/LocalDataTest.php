<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\LocalData;

use Dbp\Relay\CoreBundle\Tests\Rest\TestDataProvider;
use Dbp\Relay\CoreBundle\Tests\Rest\TestEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LocalDataTest extends TestCase
{
    /** @var TestDataProvider */
    private $testDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $localDataEventSubscriber = new TestEntityLocalDataEventSubscriber();
        $localDataEventSubscriber->setConfig(self::createSubscriberConfig());

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($localDataEventSubscriber);

        $this->testDataProvider = TestDataProvider::create($eventDispatcher);
        $this->testDataProvider->setConfig(self::createAuthzConfig());
    }

    public function testScalarLocalDataMappingWithScalarSourceValue()
    {
        // scalar attribute, scalar source attribute  -> return scalar source attribute value
        $localDataAttributeName = 'attribute_1';
        $sourceData = ['src_attribute_1' => 'value_1'];
        $testEntity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testScalarLocalDataMappingWithArraySourceValue()
    {
        // scalar attribute, array source attribute -> return scalar source attribute value (i.e. first array element)
        $localDataAttributeName = 'attribute_1';
        $sourceData = ['src_attribute_1' => ['value_1']];
        $testEntity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertEquals('value_1', $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testArrayLocalDataMappingWithArraySourceValue()
    {
        // array attribute, array source attribute -> return array source attribute value
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = ['array_src_attribute_1' => ['value_1']];
        $testEntity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertEquals(['value_1'], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testArrayLocalDataMappingWithScalarSourceValue()
    {
        // array attribute, array source attribute -> return array with scalar source value as only element
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = ['array_src_attribute_1' => 'value_1'];
        $testEntity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertEquals(['value_1'], $testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultValue()
    {
        // source data attribute of non-array attribute not available -> local data attribute of entity must be null
        $localDataAttributeName = 'attribute_1';
        $sourceData = [];
        $testEntity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertNull($testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataMappingDefaultArrayValue()
    {
        // source data attribute of array type attribute not available -> local data attribute of entity must be null
        $localDataAttributeName = 'array_attribute_1';
        $sourceData = [];
        $testEntity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertNull($testEntity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataAttributeUnauthorized()
    {
        // authorization expression of attribute evaluates to false -> forbidden exception
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        $entity = $this->getTestEntity($localDataAttributeName, $sourceData);
        $this->assertNull($entity->getLocalDataValue($localDataAttributeName));
    }

    public function testLocalDataAttributeUnauthorizedCollection()
    {
        // authorization expression of attribute evaluates to false -> local data attribute of all entities must be null
        $localDataAttributeName = 'attribute_3';
        $sourceData = ['src_attribute_3' => 'value_3'];

        $entities = $this->getTestEntities($localDataAttributeName, [
            '0' => $sourceData,
            '1' => $sourceData,
            '2' => $sourceData,
        ]);

        $this->assertNull($entities[0]->getLocalDataValue($localDataAttributeName));
        $this->assertNull($entities[1]->getLocalDataValue($localDataAttributeName));
        $this->assertNull($entities[2]->getLocalDataValue($localDataAttributeName));
    }

    private static function createAuthzConfig(): array
    {
        $config = [];
        $config['local_data'] = [
            [
                'local_data_attribute' => 'attribute_1',
                'read_policy' => 'true',
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'read_policy' => 'true',
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'read_policy' => 'false',
            ],
            [
                'local_data_attribute' => 'attribute_4',
                'read_policy' => 'true',
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
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
            ],
            [
                'local_data_attribute' => 'attribute_2',
                'source_attribute' => 'src_attribute_2_1',
            ],
            [
                'local_data_attribute' => 'attribute_3',
                'source_attribute' => 'src_attribute_3',
            ],
            [
                'local_data_attribute' => 'attribute_4',
                'source_attribute' => 'src_attribute_4',
            ],
            [
                'local_data_attribute' => 'array_attribute_1',
                'source_attribute' => 'array_src_attribute_1',
                'is_array' => true,
            ],
        ];

        return $config;
    }

    private function getTestEntity(string $includeLocal, array $sourceData): ?TestEntity
    {
        $filters = [
            'includeLocal' => $includeLocal,
        ];

        return $this->testDataProvider->getTestEntity('id', $filters, ['id' => $sourceData]);
    }

    private function getTestEntities(string $includeLocal, array $sourceData): array
    {
        $filters = [
            'includeLocal' => $includeLocal,
        ];

        return $this->testDataProvider->getTestEntities($filters, $sourceData);
    }
}
