<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestUtils;

use Dbp\Relay\CoreBundle\Tests\Rest\TestEntity;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceCollectionController;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceItemController;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;
use Dbp\Relay\CoreBundle\TestUtils\CustomControllerTester;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class CustomControllerTesterTest extends TestCase
{
    public function testItemOperation(): void
    {
        $testResourceController = new TestResourceItemController(new TestResourceService());
        $customControllerTester = CustomControllerTester::create(
            $testResourceController,
            TestEntity::class,
            normalizationGroups: ['TestEntity:output'],
            denormalizationGroups: ['TestEntity:input']);

        /** @var TestResource $testResource */
        $testResource = $customControllerTester->performItemOperation('test_id', Request::METHOD_GET,
            ['test' => 'CustomControllerTesterTest_ItemOperation', 'content' => 'test_content'], ['isPublic' => true],
            ['file' => new UploadedFile(__FILE__, 'test.txt', test: true)]);

        $this->assertEquals('test_id', $testResource->getIdentifier());
        $this->assertEquals('test_content', $testResource->getContent());
        $this->assertEquals(true, $testResource->getIsPublic());
        $this->assertEquals('test.txt', $testResource->getFile()->getClientOriginalName());
    }

    public function testCollectionOperation(): void
    {
        $testResourceCollectionController = new TestResourceCollectionController();
        $customControllerTester = CustomControllerTester::create(
            $testResourceCollectionController,
            TestEntity::class,
            normalizationGroups: ['TestEntity:output'],
            denormalizationGroups: ['TestEntity:input']);

        /** @var TestResource[] $testResources */
        $testResources = $customControllerTester->performCollectionOperation(Request::METHOD_GET,
            ['test' => 'CustomControllerTesterTest_CollectionOperation', 'content' => 'test_content'], ['isPublic' => true],
            ['file' => new UploadedFile(__FILE__, 'test.txt', test: true)]);

        $this->assertCount(1, $testResources);
        $this->assertEquals('test_content', $testResources[0]->getContent());
        $this->assertEquals(true, $testResources[0]->getIsPublic());
        $this->assertEquals('test.txt', $testResources[0]->getFile()->getClientOriginalName());
    }
}
