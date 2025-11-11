<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;

class ApiErrorTest extends AbstractApiTest
{
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=ApiErrorDetails',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller_json?test=ApiErrorDetails',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=ApiErrorDetailsDefault',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=ApiError',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=ApiError500',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=HttpException418',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=HttpException500',
            options: [
                'headers' => [
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
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=UnhandledError',
            options: [
                'headers' => [
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
    }

    public function testUnhandledErrorJsonLd()
    {
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=UnhandledError',
            options: [
                'headers' => [
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

        $this->assertTrue($this->testClient->getClient()->getKernel()->isDebug());
        $this->assertArrayHasKey('trace', $content);
    }

    public function testUnhandledErrorJson()
    {
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller_json?test=UnhandledError',
            options: [
                'headers' => [
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

        $this->assertTrue($this->testClient->getClient()->getKernel()->isDebug());
        $this->assertArrayHasKey('trace', $content);
    }

    public function testUnhandledWithoutDebug()
    {
        $this->setUpTestClient(['debug' => false]);
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=UnhandledError',
            options: [
                'headers' => [
                    'Accept' => 'application/ld+json',
                ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        // No trace with debug
        $this->assertFalse($this->testClient->getClient()->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);

        // No details with 5xx and debug
        $this->assertSame($content['detail'], 'Internal Server Error');
        $this->assertSame($content['status'], 500);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'Internal Server Error');
    }

    public function testHttpException500WithoutDebug()
    {
        $this->setUpTestClient(['debug' => false]);
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=HttpException500',
            options: [
                'headers' => [
                    'Accept' => 'application/ld+json',
                ],
            ]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        // No trace with debug
        $this->assertFalse($this->testClient->getClient()->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);

        // No details with 5xx and debug
        $this->assertSame($content['detail'], 'Internal Server Error');
        $this->assertSame($content['status'], 500);
        $this->assertSame($content['hydra:title'], 'Internal Server Error');
        $this->assertSame($content['hydra:description'], 'Internal Server Error');
    }

    public function testHttpException400WithoutDebug()
    {
        $this->setUpTestClient(['debug' => false]);
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=HttpException418',
            options: [
                'headers' => [
                    'Accept' => 'application/ld+json',
                ],
            ]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        // No trace with debug
        $this->assertFalse($this->testClient->getClient()->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);

        // No details with 5xx and debug
        $this->assertSame($content['detail'], 'not again');
        $this->assertSame($content['status'], 418);
        $this->assertSame($content['hydra:title'], "I'm a teapot");
        $this->assertSame($content['hydra:description'], 'not again');
    }

    public function testApiError500NoDebug()
    {
        $this->setUpTestClient(['debug' => false]);
        $response = $this->testClient->get(
            '/test/test-resources/foobar/custom_controller?test=ApiError500',
            options: [
                'headers' => [
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
        $this->assertFalse($this->testClient->getClient()->getKernel()->isDebug());
        $this->assertArrayNotHasKey('trace', $content);
    }
}
