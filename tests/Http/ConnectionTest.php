<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Http;

use Dbp\Relay\CoreBundle\Http\Connection;
use Dbp\Relay\CoreBundle\Http\ConnectionException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private const BASE_URI = 'https://example.com/api/';

    public function testRequest(): void
    {
        $connection = $this->createConnection(new Response(200, [], 'foo'));
        $response = $connection->get('resource');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('foo', (string) $response->getBody());
    }

    public function testClientException(): void
    {
        $connection = $this->createConnection(new Response(404, [], 'not found'));
        try {
            $connection->get('resource');
            $this->fail('ConnectionException expected');
        } catch (ConnectionException $connectionException) {
            $this->assertSame(ConnectionException::CLIENT_EXCEPTION, $connectionException->getType());
            $this->assertSame(404, $connectionException->getCode());
            $this->assertNotNull($connectionException->getRequest());
            $this->assertSame(self::BASE_URI.'resource', (string) $connectionException->getRequest()->getUri());
            $this->assertNotNull($connectionException->getResponse());
            $this->assertSame(404, $connectionException->getResponse()->getStatusCode());
        }
    }

    public function testServerException(): void
    {
        $connection = $this->createConnection(new Response(500));
        try {
            $connection->postJSON('resource', ['foo' => 'bar']);
            $this->fail('ConnectionException expected');
        } catch (ConnectionException $connectionException) {
            $this->assertSame(ConnectionException::SERVER_EXCEPTION, $connectionException->getType());
            $this->assertSame(500, $connectionException->getCode());
            $this->assertNotNull($connectionException->getRequest());
            $this->assertNotNull($connectionException->getResponse());
            $this->assertSame(500, $connectionException->getResponse()->getStatusCode());
        }
    }

    public function testNetworkException(): void
    {
        $request = new Request(Connection::REQUEST_METHOD_GET, self::BASE_URI.'resource');
        $connection = $this->createConnection(new ConnectException('connection refused', $request));
        try {
            $connection->get('resource');
            $this->fail('ConnectionException expected');
        } catch (ConnectionException $connectionException) {
            $this->assertSame(ConnectionException::NETWORK_EXCEPTION, $connectionException->getType());
            $this->assertNotNull($connectionException->getRequest());
            $this->assertNull($connectionException->getResponse());
        }
    }

    public function testRedirectionException(): void
    {
        $request = new Request(Connection::REQUEST_METHOD_GET, self::BASE_URI.'resource');
        $connection = $this->createConnection(
            new TooManyRedirectsException('too many redirects', $request, new Response(302)));
        try {
            $connection->get('resource');
            $this->fail('ConnectionException expected');
        } catch (ConnectionException $connectionException) {
            $this->assertSame(ConnectionException::REDIRECTION_EXCEPTION, $connectionException->getType());
            $this->assertNotNull($connectionException->getRequest());
            $this->assertNotNull($connectionException->getResponse());
            $this->assertSame(302, $connectionException->getResponse()->getStatusCode());
        }
    }

    public function testOtherException(): void
    {
        $request = new Request(Connection::REQUEST_METHOD_GET, self::BASE_URI.'resource');
        $connection = $this->createConnection(new RequestException('request failed', $request));
        try {
            $connection->get('resource');
            $this->fail('ConnectionException expected');
        } catch (ConnectionException $connectionException) {
            $this->assertSame(ConnectionException::OTHER_EXCEPTION, $connectionException->getType());
            $this->assertNotNull($connectionException->getRequest());
            $this->assertNull($connectionException->getResponse());
        }
    }

    private function createConnection(mixed $queued): Connection
    {
        $connection = new Connection(self::BASE_URI);
        $connection->setClientHandler(new MockHandler([$queued]));

        return $connection;
    }
}
