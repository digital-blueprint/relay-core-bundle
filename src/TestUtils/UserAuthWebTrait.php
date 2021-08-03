<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trait UserAuthWebTrait
{
    public function withUser(?string $id, array $roles = []): KernelBrowser
    {
        KernelTestCase::ensureKernelShutdown();
        $client = WebTestCase::createClient();
        $container = $client->getContainer();
        $user = new TestUser($id, $roles);
        $session = $container->get(TestUserSession::class);
        assert($session instanceof TestUserSession);
        $session->setRoles($roles);
        $session->setIdentifier($id);

        $client->loginUser($user, 'api');

        return $client;
    }

    public function getUser(KernelBrowser $client): UserInterface
    {
        $tokenStorage = $client->getContainer()->get(TokenStorageInterface::class);
        assert($tokenStorage instanceof TokenStorageInterface);

        return $tokenStorage->getToken()->getUser();
    }
}
