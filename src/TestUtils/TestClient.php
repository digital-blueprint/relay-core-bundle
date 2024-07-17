<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestUser;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestClient
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setUpUser(?string $userIdentifier = 'test user', array $userAttributes = [],
        array $symfonyRoles = [], ?string $token = TestAuthenticator::TEST_TOKEN): void
    {
        $container = $this->client->getContainer();

        $testAuthenticator = $container->get(TestAuthenticator::class);
        assert($testAuthenticator instanceof TestAuthenticator);
        $testAuthenticator->setToken($token);
        $testAuthenticator->setUser(new TestUser($userIdentifier, $symfonyRoles));

        $userAttributeProviderProvider = $container->get(TestUserAttributeProviderProvider::class);
        $userAttributeProviderProvider->addUser($userIdentifier, $userAttributes);
    }

    /**
     * HTTP GET request.
     *
     * @param array $query   Associative array of query parameters
     * @param array $options Array of request options to apply
     */
    public function get(string $url, array $query = [], array $options = [],
        ?string $token = TestAuthenticator::TEST_TOKEN): ResponseInterface
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
        ?string $token = TestAuthenticator::TEST_TOKEN): ResponseInterface
    {
        $options['json'] = $data;

        return $this->request('POST', $uri, $options, $token);
    }

    /**
     * HTTP POST request with a body in JSON format.
     *
     * @param mixed $data    Data to JSON-encode and send (must be JSON encode-able)
     * @param array $options Array of request options to apply
     */
    public function patchJson(string $url, mixed $data, array $options = [],
        ?string $token = TestAuthenticator::TEST_TOKEN): ResponseInterface
    {
        $options['json'] = $data;

        return $this->request('PATCH', $url, $options, $token);
    }

    /**
     * HTTP DELETE request.
     *
     * @param array $options Array of request options to apply
     */
    public function delete(string $url, array $options = [],
        ?string $token = TestAuthenticator::TEST_TOKEN): ResponseInterface
    {
        return $this->request('DELETE', $url, $options, $token);
    }

    public function request(string $method, string $url, array $options = [],
        ?string $token = TestAuthenticator::TEST_TOKEN): ResponseInterface
    {
        try {
            $options = array_merge($options, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]]);

            return $this->client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
