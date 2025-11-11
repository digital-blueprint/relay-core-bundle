<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResourceEntityManager;
use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;
use Dbp\Relay\CoreBundle\TestUtils\TestClient;
use Symfony\Component\HttpFoundation\Response;

class ApiTest extends AbstractApiTest
{
    public function testIndex()
    {
        $response = $this->testClient->get('/');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testError()
    {
        $response = $this->testClient->get('/errors/444');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testJSONLD()
    {
        $response = $this->testClient->get('/', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent(false));
    }

    public function testSimpleProviderNoAuth()
    {
        $response = $this->testClient->get('/test/test-resources/foobar', token: null);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testSimpleProviderAuth()
    {
        $response = $this->testClient->get('/test/test-resources/foobar');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['identifier'], 'foobar');
    }

    public function testGetCurrentUserNoAuth()
    {
        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=GetCurrentUser', token: null);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['isAuthenticated'], false);
        $this->assertSame($content['userIdentifier'], null);
        $this->assertSame($content['userRoles'], []);
    }

    public function testGetCurrentUserIdAuth()
    {
        $this->testClient->setUpUser(TestClient::TEST_USER_IDENTIFIER, symfonyRoles: ['myrole']);
        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=GetCurrentUser');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['userIdentifier'], TestClient::TEST_USER_IDENTIFIER);
        $this->assertSame($content['isAuthenticated'], true);
        $this->assertSame($content['userRoles'], ['myrole']);
    }

    public function testGetSystemUserAuth()
    {
        $this->testClient->setUpUser(null, symfonyRoles: ['myrole42']);
        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=GetCurrentUser');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['userIdentifier'], null);
        $this->assertSame($content['isAuthenticated'], true);
        $this->assertSame($content['userRoles'], ['myrole42']);
    }

    public function testNeedsAuthenticatedFully()
    {
        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY', token: null);
        $this->assertSame(401, $response->getStatusCode());

        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY', token: 'wrong');
        $this->assertSame(401, $response->getStatusCode());

        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNeedsSymfonyRole()
    {
        $this->testClient->setUpUser(symfonyRoles: ['ROLE_FOO']);
        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=ROLE_BAR');
        $this->assertSame(403, $response->getStatusCode());

        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=ROLE_FOO');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHydraPrefixesInGetCollectionResponse(): void
    {
        $testClient = new TestClient(ApiTestCase::createClient());
        $testClient->setUpUser(userAttributes: TestApiAuthorizationService::DEFAULT_USER_ATTRIBUTES);
        TestResourceEntityManager::setUp($testClient->getContainer());
        $response = $testClient->get('/test/test-resources');
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('hydra:member', $content);
        $this->assertArrayHasKey('hydra:view', $content);
    }
}
