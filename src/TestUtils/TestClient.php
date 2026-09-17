<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestClient
{
    public const TEST_USER_IDENTIFIER = 'testuser';
    public const TEST_TOKEN = TestAuthenticator::TEST_TOKEN;

    protected KernelBrowser $client;

    /**
     * @param KernelBrowser $client A Symfony test client. Create it in your test's setUp() method like this:
     *                              self::createClient() (inherited from AbstractApiTest / Symfony's WebTestCase)
     */
    public function __construct(KernelBrowser $client)
    {
        $client->followRedirects(false);
        $this->client = $client;
    }

    public function getKernelBrowser(): KernelBrowser
    {
        return $this->client;
    }

    /**
     * Setups up a user that can authenticate in subsequent requests using the given token.
     *
     * Example to perform an unauthenticated request:
     * $testClient->setUpUser(token: null);
     * $testClient->get($url, token: null);
     *
     * Example to perform an authenticated request:
     * $testClient->setUpUser();
     * $testClient->get($url);
     *
     * @param string|null $userIdentifier The user identifier
     * @param array       $userAttributes Associative array of user attributes used for authorization
     * @param array       $symfonyRoles   The symfony roles for the user
     * @param string|null $token          The bearer token the user can be authenticated with on subsequent requests.
     *                                    Use null if you want to test unauthenticated requests.
     */
    public function setUpUser(?string $userIdentifier = self::TEST_USER_IDENTIFIER, array $userAttributes = [],
        array $symfonyRoles = [], ?string $token = self::TEST_TOKEN): void
    {
        $container = $this->client->getContainer();

        $testAuthenticator = $container->get(TestAuthenticator::class);
        assert($testAuthenticator instanceof TestAuthenticator);
        $testAuthenticator->setToken($token);
        $testAuthenticator->setUser(new TestUser($userIdentifier, $symfonyRoles));

        $userAttributeProviderProvider = $container->get(TestUserAttributeProviderProvider::class);
        assert($userAttributeProviderProvider instanceof TestUserAttributeProviderProvider);
        $userAttributeProviderProvider->setDefaultAttributes($userAttributes);
        $userAttributeProviderProvider->addUser($userIdentifier, $userAttributes);
    }

    /**
     * HTTP GET request.
     *
     * @param array $query   Associative array of query parameters
     * @param array $options Array of request options to apply
     */
    public function get(string $url, array $query = [], array $options = [],
        ?string $token = self::TEST_TOKEN): ResponseInterface
    {
        $options['query'] = $query;

        return $this->request('GET', $url, $options, $token);
    }

    /**
     * HTTP POST request with a body in JSON format.
     *
     * @param mixed $data    Data to JSON-encode and send (must be JSON encode-able)
     * @param array $options Array of request options to apply
     */
    public function postJson(string $uri, mixed $data, array $options = [],
        ?string $token = self::TEST_TOKEN): ResponseInterface
    {
        $options['json'] = $data;
        $options['headers']['Content-Type'] = 'application/ld+json';

        return $this->request('POST', $uri, $options, $token);
    }

    /**
     * HTTP PATCH request with a body in JSON format.
     *
     * @param mixed $data    Data to JSON-encode and send (must be JSON encode-able)
     * @param array $options Array of request options to apply
     */
    public function patchJson(string $url, mixed $data, array $options = [],
        ?string $token = self::TEST_TOKEN): ResponseInterface
    {
        $options['json'] = $data;
        $options['headers']['Content-Type'] = 'application/merge-patch+json';

        return $this->request('PATCH', $url, $options, $token);
    }

    /**
     * HTTP DELETE request.
     *
     * @param array $options Array of request options to apply
     */
    public function delete(string $url, array $options = [],
        ?string $token = self::TEST_TOKEN): ResponseInterface
    {
        return $this->request('DELETE', $url, $options, $token);
    }

    /**
     * Performs an HTTP request against the test kernel.
     *
     * Supported options:
     *  - 'query':   array<string, mixed>            Query parameters appended to the URL
     *  - 'json':    mixed                           JSON-encoded and sent as the request body
     *  - 'body':    string                          Raw request body (ignored if 'json' is set)
     *  - 'headers': array<string, string|string[]>  Request headers
     */
    public function request(string $method, string $url, array $options = [],
        ?string $token = self::TEST_TOKEN): ResponseInterface
    {
        if ($token !== null) {
            $options['headers']['Authorization'] = 'Bearer '.$token;
        }

        $query = $options['query'] ?? [];
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        $body = null;
        if (array_key_exists('json', $options) && $options['json'] !== null) {
            $body = json_encode($options['json'], JSON_THROW_ON_ERROR);
            $options['headers']['Content-Type'] ??= 'application/json';
        } elseif (isset($options['body'])) {
            $body = $options['body'];
        }

        $server = $this->headersToServer($options['headers'] ?? []);

        $this->client->request($method, $url, [], [], $server, $body);

        return new TestResponse(
            $this->client->getResponse(),
            $this->client->getInternalResponse(),
            ['http_method' => $method, 'url' => $url]
        );
    }

    /**
     * Converts an associative array of HTTP headers into BrowserKit's $_SERVER format.
     *
     * Raw $_SERVER-style keys (e.g. 'HTTP_ACCEPT', 'CONTENT_TYPE') are passed through as-is.
     *
     * @param array<string, string|string[]> $headers
     *
     * @return array<string, string>
     */
    private function headersToServer(array $headers): array
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $value = is_array($value) ? ($value[0] ?? '') : $value;

            if (str_starts_with($name, 'HTTP_') || in_array($name, ['CONTENT_TYPE', 'REMOTE_ADDR'], true)) {
                $server[$name] = $value;
                continue;
            }

            $normalized = strtoupper(str_replace('-', '_', $name));
            $server[in_array($normalized, ['CONTENT_TYPE', 'REMOTE_ADDR'], true)
                ? $normalized
                : 'HTTP_'.$normalized] = $value;
        }

        return $server;
    }
}
