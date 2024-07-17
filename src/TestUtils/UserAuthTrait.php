<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait UserAuthTrait
{
    public function withUser(?string $userIdentifier, array $symfonyRoles = [], ?string $token = null): Client
    {
        KernelTestCase::ensureKernelShutdown();

        $client = ApiTestCase::createClient();
        $container = $client->getContainer();

        $auth = $container->get(TestAuthenticator::class);
        assert($auth instanceof TestAuthenticator);
        $auth->setToken($token);
        $auth->setUser(new TestUser($userIdentifier, $symfonyRoles));

        return $client;
    }

    /**
     * Sets up a user with the given list of user attributes.
     * For the authentication in subsequent requests to work you have to add an 'Authorization' header with the value
     * TestAuthenticator::TEST_AUTHORIZATION_HEADER:
     *
     * $client = $this->withUserAttributes('testuser', ['my_user_attribute' => 'my_value',]);
     * $response = $client->request('GET', $url, [
     *     'headers' => ['Authorization' => TestAuthenticator::TEST_AUTHORIZATION_HEADER]]);
     *
     * @param array $userAttributes An associative array of user attributes (key: attribute name, value: attribute value)
     */
    public function withUserAttributes(string $userIdentifier, array $userAttributes): Client
    {
        $client = $this->withUser($userIdentifier, [], TestAuthenticator::TEST_TOKEN);

        $container = $client->getContainer();
        $userAttributeProviderProvider = $container->get(TestUserAttributeProviderProvider::class);
        $userAttributeProviderProvider->addUser($userIdentifier, $userAttributes);

        return $client;
    }

    public function getUser(Client $client): ?UserInterface
    {
        $container = $client->getContainer();
        $auth = $container->get(TestAuthenticator::class);
        assert($auth instanceof TestAuthenticator);

        return $auth->getUser();
    }

    public function getTestResponse(string $method, string $url, array $options = [], ?string $userIdentifier = 'testuser', array $userAttributes = []): ResponseInterface
    {
        try {
            $client = $this->withUserAttributes($userIdentifier, $userAttributes);
            $options = array_merge($options, [
                'headers' => [
                    'Authorization' => 'Bearer '.TestAuthenticator::TEST_TOKEN,
                ]]);

            return $client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function getTestResponseUnauthenticated(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $client = $this->withUser(null);

            return $client->request($method, $url);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
