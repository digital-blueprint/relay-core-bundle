<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Exception\ApiErrorNormalizer;
use Dbp\Relay\CoreBundle\TestUtils\UserAuthTrait;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;

class ApiErrorTest extends ApiTestCase
{
    use UserAuthTrait;

    public function testBasics()
    {
        $error = new ApiError(400, 'foobar');
        $message = json_decode($error->getMessage(), true);
        $this->assertSame('foobar', $message['message']);
        $this->assertSame(400, $error->getStatusCode());
        $this->assertSame('foobar', $error->getErrorMessage());
        $this->assertSame(null, $error->getErrorDetails());
        $this->assertSame(null, $error->getErrorId());
    }

    public function testWithDetails()
    {
        $error = ApiError::withDetails(424, 'message', 'id', ['foo' => 'bar']);
        $message = json_decode($error->getMessage(), true);
        $this->assertSame('message', $message['message']);
        $this->assertSame('id', $message['errorId']);
        $this->assertSame(['foo' => 'bar'], $message['errorDetails']);
        $this->assertSame(424, $error->getStatusCode());
        $this->assertSame('message', $error->getErrorMessage());
        $this->assertSame(['foo' => 'bar'], $error->getErrorDetails());
        $this->assertSame('id', $error->getErrorId());
    }

    private function normalize(ApiError $error, string $format): array
    {
        $norm = new ApiErrorNormalizer();
        $norm->setNormalizer(new ProblemNormalizer());
        $exc = FlattenException::createFromThrowable($error, $error->getStatusCode());

        return $norm->normalize($exc, $format);
    }

    public function testNormalizer()
    {
        $error = ApiError::withDetails(424, 'message', 'id', ['foo' => 'bar']);
        $res = self::normalize($error, 'jsonld');
        $this->assertSame($res['status'], 424);
        $this->assertSame($res['hydra:description'], 'message');
        $this->assertSame($res['relay:errorId'], 'id');
        $this->assertSame((array) $res['relay:errorDetails'], ['foo' => 'bar']);

        $res = self::normalize($error, 'jsonproblem');
        $this->assertSame($res['status'], 424);
        $this->assertSame($res['detail'], 'message');
        $this->assertSame($res['errorId'], 'id');
        $this->assertSame((array) $res['errorDetails'], ['foo' => 'bar']);

        $error = ApiError::withDetails(424, 'message', 'id');
        $res = self::normalize($error, 'jsonld');
        $this->assertSame($res['status'], 424);
        $this->assertSame($res['hydra:description'], 'message');
        $this->assertSame($res['relay:errorId'], 'id');
        $this->assertIsObject($res['relay:errorDetails']);
        $this->assertSame((array) $res['relay:errorDetails'], []);

        $error = ApiError::withDetails(424, 'message', 'id', ['foo', 'bar']);
        $res = self::normalize($error, 'jsonld');
        $this->assertSame($res['status'], 424);
        $this->assertSame($res['hydra:description'], 'message');
        $this->assertSame($res['relay:errorId'], 'id');
        $this->assertIsObject($res['relay:errorDetails']);
        $this->assertSame((array) $res['relay:errorDetails'], ['foo', 'bar']);
    }

    public function testApiErrorDetailsJsonLd()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiErrorDetails', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($content['hydra:title'], 'An error occurred');
        $this->assertSame($content['hydra:description'], 'some message');
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
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller_json?test=ApiErrorDetails', ['headers' => ['Accept' => 'application/json']]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['title'], 'An error occurred');
        $this->assertSame($content['detail'], 'some message');
        $this->assertSame($content['errorId'], 'some-error-id');
        $this->assertSame($content['errorDetails'], [
            'detail1' => '1',
            'detail2' => ['2', '3'],
        ]);
        $content = json_decode($response->getContent(false), false, flags: JSON_THROW_ON_ERROR);
        $this->assertIsObject($content->errorDetails);
        $this->assertIsArray($content->errorDetails->detail2);
    }

    public function testApiErrorDetailsDefaultJsonLd()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiErrorDetailsDefault', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'An error occurred');
        $this->assertSame($content['hydra:description'], '');
        $this->assertSame($content['relay:errorId'], '');
        $this->assertSame($content['relay:errorDetails'], []);
        $content = json_decode($response->getContent(false), false, flags: JSON_THROW_ON_ERROR);
        $this->assertIsObject($content->{'relay:errorDetails'});
    }

    public function testApiError()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=ApiError', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'An error occurred');
        $this->assertSame($content['hydra:description'], '');
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);
    }

    public function testUnhandledError()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller?test=UnhandledError');
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/ld+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($content['hydra:title'], 'An error occurred');
        $this->assertSame($content['hydra:description'], 'oh no');
        $this->assertArrayNotHasKey('relay:errorId', $content);
        $this->assertArrayNotHasKey('relay:errorDetails', $content);
    }

    public function testUnhandledErrorJson()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/test/test-resources/foobar/custom_controller_json?test=UnhandledError', ['headers' => ['Accept' => 'application/json']]);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaders(false)['content-type'][0]);
        $content = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($content['title'], 'An error occurred');
        $this->assertSame($content['detail'], 'oh no');
        $this->assertArrayNotHasKey('errorId', $content);
        $this->assertArrayNotHasKey('errorDetails', $content);
    }
}
