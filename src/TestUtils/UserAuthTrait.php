<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

trait UserAuthTrait
{
    public function withUser(string $userIdentifier, array $symfonyRoles = [], ?string $token = null): Client
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

    public function withUserAttributes(string $userIdentifier, array $userAttributes): Client
    {
        $client = $this->withUser($userIdentifier);

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
}
