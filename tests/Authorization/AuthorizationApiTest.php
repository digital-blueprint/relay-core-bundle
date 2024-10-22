<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResource;
use Dbp\Relay\CoreBundle\TestUtils\TestClient;
use Dbp\Relay\CoreBundle\TestUtils\UserAuthTrait;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AuthorizationApiTest extends ApiTestCase
{
    use UserAuthTrait;

    private function getTestClient(string $userIdentifier = 'testuser', array $userAttributes = ['IS_ADMIN' => false]): TestClient
    {
        $testClient = new TestClient(ApiTestCase::createClient());
        $testClient->setUpUser(userIdentifier: $userIdentifier, userAttributes: $userAttributes);

        return $testClient;
    }

    public function testGetTestResourceDefault(): void
    {
        $response = $this->getTestClient()->get('/test/test-resources/foo?test_output_groups=default');
        $testResource = $this->deserializeTestResource($response);

        $this->assertEquals('foo', $testResource->getIdentifier());
        $this->assertNull($testResource->getContent());
        $this->assertNull($testResource->getSecret());
    }

    public function testAddOutputGroupForEntityClassByRole(): void
    {
        $response = $this->getTestClient(userAttributes: ['IS_ADMIN' => true])->get('/test/test-resources/foo?test_output_groups=class_by_role');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        $response = $this->getTestClient()->get('/test/test-resources/foo?test_output_groups=class_by_role');
        $testResource = $this->deserializeTestResource($response);
        $this->assertNull($testResource->getSecret());
    }

    public function testAddOutputGroupForEntityClassByCondition(): void
    {
        $response = $this->getTestClient(userIdentifier: 'king_arthur')->get('/test/test-resources/foo?test_output_groups=class_by_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        $response = $this->getTestClient()->get('/test/test-resources/foo?test_output_groups=class_by_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertNull($testResource->getSecret());
    }

    public function testAddOutputGroupForEntityClassByRoleAndCondition(): void
    {
        // both true
        $response = $this->getTestClient(userIdentifier: 'king_arthur', userAttributes: ['IS_ADMIN' => true])->get(
            '/test/test-resources/foo?test_output_groups=class_by_role_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        // one true
        $response = $this->getTestClient(userIdentifier: 'king_arthur')->get(
            '/test/test-resources/foo?test_output_groups=class_by_role_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        // other true
        $response = $this->getTestClient(userAttributes: ['IS_ADMIN' => true])->get(
            '/test/test-resources/foo?test_output_groups=class_by_role_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        // none true
        $response = $this->getTestClient()->get(
            '/test/test-resources/foo?test_output_groups=class_by_role_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertNull($testResource->getSecret());
    }

    public function testAddOutputGroupForEntityByResourcePermission(): void
    {
        $response = $this->getTestClient(userIdentifier: 'admin')->get(
            '/test/test-resources/admin?test_output_groups=entity_by_resource_permission');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        $response = $this->getTestClient()->get('/test/test-resources/foo?test_output_groups=entity_by_resource_permission');
        $testResource = $this->deserializeTestResource($response);
        $this->assertNull($testResource->getSecret());
    }

    public function testAddOutputGroupForEntityByCondition(): void
    {
        $response = $this->getTestClient()->get('/test/test-resources/public?test_output_groups=entity_by_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        $response = $this->getTestClient()->get('/test/test-resources/foo?test_output_groups=entity_by_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertNull($testResource->getSecret());
    }

    public function testAddOutputGroupForEntityByResourcePermissionAndCondition(): void
    {
        // both true
        $response = $this->getTestClient(userIdentifier: 'public')->get(
            '/test/test-resources/public?test_output_groups=entity_by_resource_permission_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        // one true
        $response = $this->getTestClient()->get(
            '/test/test-resources/public?test_output_groups=entity_by_resource_permission_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        // other true
        $response = $this->getTestClient(userIdentifier: 'admin')->get(
            '/test/test-resources/public?test_output_groups=entity_by_resource_permission_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertEquals('secret', $testResource->getSecret());

        // none true
        $response = $this->getTestClient(userIdentifier: 'bar')->get(
            '/test/test-resources/foo?test_output_groups=entity_by_resource_permission_and_condition');
        $testResource = $this->deserializeTestResource($response);
        $this->assertNull($testResource->getSecret());
    }

    private function deserializeTestResource(ResponseInterface $response): TestResource
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->deserialize($response->getContent(false), TestResource::class, 'json');
    }
}
