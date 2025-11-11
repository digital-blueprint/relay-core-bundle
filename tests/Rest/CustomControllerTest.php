<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;

class CustomControllerTest extends AbstractApiTest
{
    public function testCustomControllerRequiredAuthenticationAuthenticated()
    {
        $response = $this->testClient->get('/test/test-resources/foobar/custom_controller?test=GetResourceAuthenticatedOnly');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['identifier'], 'foobar');
    }

    public function testCustomControllerRequiredAuthenticationUnauthenticated()
    {
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=GetResourceAuthenticatedOnly', token: null);
        $this->assertSame(401, $response->getStatusCode());
    }
}
