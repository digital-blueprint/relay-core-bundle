<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestUtils;

use Dbp\Relay\CoreBundle\Tests\Rest\TestDataProcessor;
use Dbp\Relay\CoreBundle\Tests\Rest\TestEntity;
use Dbp\Relay\CoreBundle\TestUtils\DataProcessorTester;
use PHPUnit\Framework\TestCase;

class DataProcessorTesterTest extends TestCase
{
    private DataProcessorTester $dataProcessorTester;
    private TestDataProcessor $dataProcessor;

    protected function setUp(): void
    {
        $this->dataProcessor = new TestDataProcessor();
        $this->dataProcessorTester = DataProcessorTester::create(
            $this->dataProcessor, TestEntity::class, ['TestEntity:input']);
    }

    public function testAddItem(): void
    {
        $testEntity = new TestEntity();
        $testEntity->setField0('test');

        $this->dataProcessorTester->addItem($testEntity);

        $this->assertSame($testEntity, $this->dataProcessor->getItemByIdentifier($testEntity->getIdentifier()));
    }

    public function testReplaceItem(): void
    {
        $testEntity = new TestEntity();
        $testEntity->setField0('test');
        $this->dataProcessorTester->addItem($testEntity);

        $updatedTestEntity = new TestEntity();
        $updatedTestEntity->setField0('updated test');
        $this->dataProcessorTester->replaceItem($testEntity->getIdentifier(), $updatedTestEntity, $testEntity);

        $this->assertSame($updatedTestEntity, $this->dataProcessor->getItemByIdentifier($testEntity->getIdentifier()));
    }

    public function testUpdateItem(): void
    {
        $testEntity = new TestEntity();
        $testEntity->setField0('test');
        $this->dataProcessorTester->addItem($testEntity);

        $updatedTestEntity = new TestEntity();
        $updatedTestEntity->setField0('updated test');
        $this->dataProcessorTester->updateItem($testEntity->getIdentifier(), $updatedTestEntity, $testEntity);

        $this->assertSame($updatedTestEntity, $this->dataProcessor->getItemByIdentifier($testEntity->getIdentifier()));
    }

    public function testRemoveItem(): void
    {
        $testEntity = new TestEntity();
        $testEntity->setField0('test');
        $this->dataProcessorTester->addItem($testEntity);
        $this->assertSame($testEntity, $this->dataProcessor->getItemByIdentifier($testEntity->getIdentifier()));

        $this->dataProcessorTester->removeItem($testEntity->getIdentifier(), $testEntity);

        $this->assertNull($this->dataProcessor->getItemByIdentifier($testEntity->getIdentifier()));
    }
}
