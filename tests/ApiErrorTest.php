<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\TestUtils\UserAuthTrait;

class ApiErrorTest extends ApiTestCase
{
    use UserAuthTrait;

    public function testBasics()
    {
        $error = new ApiError(400, 'foobar');
        $this->assertSame('foobar', $error->getDetail());
        $this->assertSame(400, $error->getStatusCode());
        $this->assertSame('foobar', $error->getMessage());
        $this->assertSame(null, $error->getErrorDetails());
        $this->assertSame(null, $error->getErrorId());
    }

    public function testWithDetails()
    {
        $error = ApiError::withDetails(424, 'message', 'id', ['foo' => 'bar']);
        $this->assertSame('message', $error->getDetail());
        $this->assertSame(424, $error->getStatusCode());
        $this->assertSame('message', $error->getMessage());
        $this->assertEquals(['foo' => 'bar'], (array) $error->getErrorDetails());
        $this->assertSame('id', $error->getErrorId());
    }

    public function testApiErrorDetailsJsonLd()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiErrorDetails',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'I\'m a teapot');
        $this->assertSame($content['hydra:description'], 'some message');
        $this->assertSame($content['detail'], 'some message');
        $this->assertSame($content['status'], 418);
        $this->assertSame($content['relay:errorId'], 'some-error-id');
        $this->assertSame($content['relay:errorDetails'], [
            'detail1' => '1',
            'detail2' => ['2', '3'],
        ]);

        $content = json_decode($response->getContent(false), false, flags: JSON_THROW_ON_ERROR);
        $this->assertIsObject($content->{'relay:errorDetails'});
        $this->assertIsArray($content->{'relay:errorDetails'}->detail2);
    }

    public function testApiErrorDetailsJson()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller_json?test=ApiErrorDetails',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/json',
            ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['title'], 'I\'m a teapot');
        $this->assertSame($content['detail'], 'some message');
        $this->assertSame($content['status'], 418);
        $this->assertSame($content['relay:errorId'], 'some-error-id');
        $this->assertSame($content['relay:errorDetails'], [
            'detail1' => '1',
            'detail2' => ['2', '3'],
        ]);

        $content = json_decode($response->getContent(false), false, flags: JSON_THROW_ON_ERROR);
        $this->assertIsObject($content->{'relay:errorDetails'});
        $this->assertIsArray($content->{'relay:errorDetails'}->detail2);
    }

    public function testApiErrorDetailsDefaultJsonLd()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiErrorDetailsDefault',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'I\'m a teapot');
        $this->assertSame($content['hydra:description'], '');
        $this->assertSame($content['detail'], '');
        $this->assertSame($content['status'], 418);
        $this->assertSame($content['relay:errorId'], '');
        $this->assertSame($content['relay:errorDetails'], []);
        $content = json_decode($response->getContent(false), false, flags: JSON_THROW_ON_ERROR);
        $this->assertIsObject($content->{'relay:errorDetails'});
    }

    public function testApiError()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiError',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'I\'m a teapot');
        $this->assertSame($content['hydra:description'], '');
        $this->assertSame($content['detail'], '');
        $this->assertSame($content['status'], 418);
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);
    }

    public function testApiError500()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiError500',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], "it wasn't me");
        $this->assertSame($content['detail'], "it wasn't me");
        $this->assertSame($content['status'], 500);
    }

    public function testHttpException418()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=HttpException418',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'I\'m a teapot');
        $this->assertSame($content['hydra:description'], 'not again');
        $this->assertSame($content['detail'], 'not again');
        $this->assertSame($content['status'], 418);
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);
    }

    public function testHttpException500()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=HttpException500',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'totally unexpected');
        $this->assertSame($content['detail'], 'totally unexpected');
        $this->assertSame($content['status'], 500);
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);
    }

    public function testUnhandledErrorDefaultOutputFormat()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=UnhandledError',
            ['headers' => [
                'Authorization' => 'Bearer 42',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['detail'], 'oh no');
        $this->assertSame($content['status'], 500);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'oh no');
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);
    }

    public function testUnhandledErrorJsonLd()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=UnhandledError',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['detail'], 'oh no');
        $this->assertSame($content['status'], 500);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'oh no');
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);

        $this->assertTrue($client->getKernel()->isDebug());
        $this->assertArrayHasKey('trace', $content);
    }

    public function testUnhandledErrorJson()
    {
        $client = $this->withUser('user', [], '42');
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller_json?test=UnhandledError',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['title'], 'Internal Server Error');
        $this->assertSame($content['detail'], 'oh no');
        $this->assertSame($content['status'], 500);
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);

        $this->assertTrue($client->getKernel()->isDebug());
        $this->assertArrayHasKey('trace', $content);
    }

    public function testUnhandledWithoutDebug()
    {
        $client = $this->withUser('user', [], '42', kernelOptions: ['debug' => false]);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=UnhandledError',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        // No trace with debug
        $this->assertFalse($client->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);

        // No details with 5xx and debug
        $this->assertSame($content['detail'], 'Internal Server Error');
        $this->assertSame($content['status'], 500);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'Internal Server Error');
    }

    public function testHttpException500WithoutDebug()
    {
        $client = $this->withUser('user', [], '42', kernelOptions: ['debug' => false]);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=HttpException500',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        // No trace with debug
        $this->assertFalse($client->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);

        // No details with 5xx and debug
        $this->assertSame($content['detail'], 'Internal Server Error');
        $this->assertSame($content['status'], 500);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'Internal Server Error');
    }

    public function testHttpException400WithoutDebug()
    {
        $client = $this->withUser('user', [], '42', kernelOptions: ['debug' => false]);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=HttpException418',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        // No trace with debug
        $this->assertFalse($client->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);

        // No details with 5xx and debug
        $this->assertSame($content['detail'], 'not again');
        $this->assertSame($content['status'], 418);
        $this->assertSame($content['hydra:title'], "I'm a teapot");
        $this->assertSame($content['hydra:description'], 'not again');
    }

    public function testApiError500NoDebug()
    {
        $client = $this->withUser('user', [], '42', kernelOptions: ['debug' => false]);
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiError500',
            ['headers' => [
                'Authorization' => 'Bearer 42',
                'Accept' => 'application/ld+json',
            ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], "it wasn't me");
        $this->assertSame($content['detail'], "it wasn't me");
        $this->assertSame($content['status'], 500);
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);

        // No trace with debug
        $this->assertFalse($client->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);
    }
}
