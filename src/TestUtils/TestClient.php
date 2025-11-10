<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestUser;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestClient
{
    public const TEST_USER_IDENTIFIER = 'testuser';
    public const TEST_TOKEN = TestAuthenticator::TEST_TOKEN;

    protected Client $client;

    /**
     * @param Client $client Create in your override of ApiPlatform\Symfony\Bundle\Test\ApiTestCase::setUp method like this:
     *                       ApiTestCase::createClient()
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->client->getContainer();
    }

    /**
     * Setups up a user that can authenticate in subsequent requests using the given token.
     *
     * @param string|null $userIdentifier The user identifier
     * @param array       $userAttributes Associative array of user attributes used for authorization
     * @param array       $symfonyRoles   The symfony roles for the user
     * @param string|null $token          The bearer token the user can be authenticated with on subsequent requests
     */
    public function setUpUser(?string $userIdentifier = self::TEST_USER_IDENTIFIER, array $userAttributes = [],
        array $symfonyRoles = [], ?string $token = TestAuthenticator::TEST_TOKEN): void
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

        $userAttributeMuxer = $container->get(UserAttributeMuxer::class);
        assert($userAttributeMuxer instanceof UserAttributeMuxer);
        $userAttributeMuxer->clearRequestCaches();
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
        $options['headers']['Content-Type'] = 'application/ld+json';

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
        $options['headers']['Content-Type'] = 'application/merge-patch+json';

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
            $options['headers']['Authorization'] = 'Bearer '.$token;

            return $this->client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
