<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Tests\AbstractApiTestCase;

class EntityNormalizerApiTestCase extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->login(userAttributes: ['IS_ADMIN' => false]);
    }

    public function testGetTestResourceDefault(): void
    {
        $testResource = $this->addTestResource();
        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=default');
        $testResourceArray = $this->getTestResourceEntityData($response);

        $this->assertEquals(self::TEST_CONTENT, $testResourceArray['content']);
        $this->assertEquals(self::TEST_IS_PUBLIC, $testResourceArray['isPublic']);
        $this->assertEquals($testResource->getIdentifier(), $testResourceArray['identifier']);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testAddOutputGroupForEntityClassByRole(): void
    {
        $testResource = $this->addTestResource();
        $response = $this->getTestClient(userAttributes: ['IS_ADMIN' => true])->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_role');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_CONTENT, $testResourceArray['content']);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_role');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_CONTENT, $testResourceArray['content']);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testAddOutputGroupForEntityClassByCondition(): void
    {
        $testResource = $this->addTestResource();
        $response = $this->getTestClient(userIdentifier: 'king_arthur')->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testAddOutputGroupForEntityClassByRoleAndCondition(): void
    {
        $testResource = $this->addTestResource();
        // both true
        $response = $this->getTestClient(userIdentifier: 'king_arthur', userAttributes: ['IS_ADMIN' => true])->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_role_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        // one true
        $response = $this->getTestClient(userIdentifier: 'king_arthur')->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_role_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        // other true
        $response = $this->getTestClient(userAttributes: ['IS_ADMIN' => true])->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_role_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        // none true
        $response = $this->getTestClient()->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=class_by_role_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testAddOutputGroupForEntityByResourcePermission(): void
    {
        $testResource = $this->addTestResource(content: 'public content');
        $response = $this->getTestClient()->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_resource_permission');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        $testResource = $this->addTestResource();
        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_resource_permission');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testAddOutputGroupForEntityByCondition(): void
    {
        $testResource = $this->addTestResource(isPublic: true);
        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        $testResource = $this->addTestResource(isPublic: false);
        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testAddOutputGroupForEntityByResourcePermissionAndCondition(): void
    {
        // both true
        $testResource = $this->addTestResource(content: 'public content', isPublic: true);
        $response = $this->getTestClient(userIdentifier: 'public')->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_resource_permission_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        // one true
        $testResource = $this->addTestResource(isPublic: true);
        $response = $this->getTestClient()->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_resource_permission_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        // other true
        $testResource = $this->addTestResource(content: 'public content', isPublic: true);
        $response = $this->getTestClient(userIdentifier: 'admin')->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_resource_permission_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);

        // none true
        $testResource = $this->addTestResource();
        $response = $this->getTestClient(userIdentifier: 'bar')->get(
            '/test/test-resources/'.$testResource->getIdentifier().'?test_output_groups=entity_by_resource_permission_and_condition');
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayNotHasKey('secret', $testResourceArray);
    }

    public function testCollectionOfSubResourceOutputGroups(): void
    {
        // test whether conditional normalization groups also work for (collections of) sub-resources
        $testResource = $this->addTestResource();
        $testSubResource = $this->addTestSubResource($testResource);
        $response = $this->getTestClient()->get('/test/test-sub-resources/'.$testSubResource->getIdentifier());
        $testSubResourceArray = $this->getTestResourceEntityData($response);
        $this->assertFalse($testSubResourceArray['isPublic']);
        $this->assertArrayNotHasKey('password', $testSubResourceArray);

        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier());
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertCount(1, $testResourceArray['subResources']);
        $this->assertEquals($testSubResource->getIdentifier(), $testResourceArray['subResources'][0]['identifier']);
        $this->assertFalse($testResourceArray['subResources'][0]['isPublic']);
        $this->assertArrayNotHasKey('password', $testResourceArray['subResources'][0]);

        $testResource = $this->addTestResource();
        $testSubResource = $this->addTestSubResource($testResource, isPublic: true);
        $response = $this->getTestClient()->get('/test/test-sub-resources/'.$testSubResource->getIdentifier());
        $testSubResourceArray = $this->getTestResourceEntityData($response);
        $this->assertTrue($testSubResourceArray['isPublic']);
        $this->assertEquals(self::TEST_PASSWORD, $testSubResourceArray['password']);

        $response = $this->getTestClient()->get('/test/test-resources/'.$testResource->getIdentifier());
        $testResourceArray = $this->getTestResourceEntityData($response);
        $this->assertCount(1, $testResourceArray['subResources']);
        $this->assertEquals($testSubResource->getIdentifier(), $testResourceArray['subResources'][0]['identifier']);
        $this->assertTrue($testResourceArray['subResources'][0]['isPublic']);
        $this->assertEquals(self::TEST_PASSWORD, $testResourceArray['subResources'][0]['password']);
    }

    public function testResourceOutputGroups(): void
    {
        $testResource = $this->addTestResource();
        $testSubResource = $this->addTestSubResource($testResource);
        $response = $this->getTestClient()->get('/test/test-sub-resources/'.$testSubResource->getIdentifier());
        $testSubResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayHasKey('testResource', $testSubResourceArray);
        $testResourceArray = $testSubResourceArray['testResource'];
        $this->assertEquals($testResource->getIdentifier(), $testResourceArray['identifier']);
        $this->assertFalse($testResourceArray['isPublic']);
        $this->assertArrayNotHasKey('secret', $testResourceArray);

        $testResource = $this->addTestResource(isPublic: true);
        $testSubResource = $this->addTestSubResource($testResource);
        $response = $this->getTestClient()->get('/test/test-sub-resources/'.$testSubResource->getIdentifier());
        $testSubResourceArray = $this->getTestResourceEntityData($response);
        $this->assertArrayHasKey('testResource', $testSubResourceArray);
        $testResourceArray = $testSubResourceArray['testResource'];
        $this->assertEquals($testResource->getIdentifier(), $testResourceArray['identifier']);
        $this->assertTrue($testResourceArray['isPublic']);
        $this->assertEquals(self::TEST_SECRET, $testResourceArray['secret']);
    }
}
