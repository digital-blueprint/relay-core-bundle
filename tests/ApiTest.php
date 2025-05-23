<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResourceEntityManager;
use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\TestClient;
use Dbp\Relay\CoreBundle\TestUtils\UserAuthTrait;
use Symfony\Component\HttpFoundation\Response;

class ApiTest extends AbstractApiTest
{
    use UserAuthTrait;

    public function testIndex()
    {
        $client = $this->withUser('foobar');
        $response = $client->request('GET', '/');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testError()
    {
        $client = $this->withUser('foobar');
        $response = $client->request('GET', '/errors/444');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testJSONLD()
    {
        $client = $this->withUser('foobar');
        $response = $client->request('GET', '/', ['headers' => ['HTTP_ACCEPT' => 'application/ld+json']]);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent(false));
    }

    public function testSimpleProviderNoAuth()
    {
        $client = $this->withUser('foobar');
        $response = $client->request('GET', '/test/test-resources/foobar');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testSimpleProviderAuth()
    {
        $client = $this->withUser('foobar', token: TestAuthenticator::TEST_TOKEN);
        $response = $client->request('GET', '/test/test-resources/foobar', ['headers' => ['Authorization' => 'Bearer '.TestAuthenticator::TEST_TOKEN]]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['identifier'], 'foobar');
    }

    public function testGetCurrentUserNoAuth()
    {
        $client = $this->withUser('someuser', ['myrole']);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=GetCurrentUser');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['isAuthenticated'], false);
        $this->assertSame($content['userIdentifier'], null);
        $this->assertSame($content['userRoles'], []);
    }

    public function testGetCurrentUserIdAuth()
    {
        $client = $this->withUser('someuser', ['myrole'], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=GetCurrentUser', ['headers' => ['Authorization' => 'Bearer xxx']]);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['userIdentifier'], 'someuser');
        $this->assertSame($content['isAuthenticated'], true);
        $this->assertSame($content['userRoles'], ['myrole']);
    }

    public function testGetSystemUserAuth()
    {
        $client = $this->withUser(null, ['myrole42'], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=GetCurrentUser', ['headers' => ['Authorization' => 'Bearer xxx']]);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['userIdentifier'], null);
        $this->assertSame($content['isAuthenticated'], true);
        $this->assertSame($content['userRoles'], ['myrole42']);
    }

    public function testNoUserSetup()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=GetCurrentUser');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $content = json_decode($content['content'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['userIdentifier'], null);
        $this->assertSame($content['isAuthenticated'], false);
        $this->assertSame($content['userRoles'], []);
    }

    public function testNeedsAuthenticatedFully()
    {
        $client = $this->withUser(null);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY');
        $this->assertSame(401, $response->getStatusCode());

        $client = $this->withUser(null);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY', ['headers' => ['Authorization' => 'Bearer xxx']]);
        $this->assertSame(401, $response->getStatusCode());

        $client = $this->withUser(null, [], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY');
        $this->assertSame(401, $response->getStatusCode());

        $client = $this->withUser(null, [], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY', ['headers' => ['Authorization' => 'Bearer wrong']]);
        $this->assertSame(401, $response->getStatusCode());

        $client = $this->withUser(null, [], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=IS_AUTHENTICATED_FULLY', ['headers' => ['Authorization' => 'Bearer xxx']]);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNeedsSymfonyRole()
    {
        $client = $this->withUser(null, ['ROLE_FOO'], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=ROLE_BAR', ['headers' => ['Authorization' => 'Bearer xxx']]);
        $this->assertSame(403, $response->getStatusCode());

        $client = $this->withUser(null, ['ROLE_FOO'], 'xxx');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=denyAccessUnlessGranted&param=ROLE_FOO', ['headers' => ['Authorization' => 'Bearer xxx']]);
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
