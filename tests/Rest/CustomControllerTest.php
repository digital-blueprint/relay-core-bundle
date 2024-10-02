<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dbp\Relay\CoreBundle\TestUtils\UserAuthTrait;

class CustomControllerTest extends ApiTestCase
{
    use UserAuthTrait;

    public function testCustomControllerRequiredAuthenticationAuthenticated()
    {
        $client = $this->withUser('someuser', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=GetResourceAuthenticatedOnly', ['headers' => ['Authorization' => 'Bearer 42']]);
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['identifier'], 'foobar');
    }

    public function testCustomControllerRequiredAuthenticationUnauthenticated()
    {
        $client = $this->withUser('someuser');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=GetResourceAuthenticatedOnly');
        $this->assertSame(401, $response->getStatusCode());
    }
}
