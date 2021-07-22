<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\TestUtils\UserAuthTrait;
use Symfony\Component\HttpFoundation\Response;

class ApiTest extends ApiTestCase
{
    use UserAuthTrait;

    public function testIndex()
    {
        $client = $this->withUser('foobar');
        $response = $client->request('GET', '/');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testJSONLD()
    {
        $client = $this->withUser('foobar');
        $response = $client->request('GET', '/', ['headers' => ['HTTP_ACCEPT' => 'application/ld+json']]);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent(false));
    }
}
