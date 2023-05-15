<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

trait UserAuthTrait
{
    public function withUser(?string $id, array $roles = [], ?string $token = null): Client
    {
        KernelTestCase::ensureKernelShutdown();

        $client = ApiTestCase::createClient();
        $container = $client->getContainer();

        $session = $container->get(TestUserSession::class);
        assert($session instanceof TestUserSession);
        $session->setIdentifier($id);
        $session->setRoles($roles);

        $auth = $container->get(TestAuthenticator::class);
        assert($auth instanceof TestAuthenticator);
        $auth->setToken($token);
        $user = new TestUser($id, $roles);
        $auth->setUser($user);

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
