<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @deprecated use Dbp\Relay\CoreBundle\TestUtils\TestClient instead
 */
trait UserAuthWebTrait
{
    /**
     * @deprecated use Dbp\Relay\CoreBundle\TestUtils\TestClient::setUpUser instead
     */
    public function withUser(?string $id, array $roles = []): KernelBrowser
    {
        KernelTestCase::ensureKernelShutdown();
        $client = WebTestCase::createClient();
        $container = $client->getContainer();

        $testAuthenticator = $container->get(TestAuthenticator::class);
        assert($testAuthenticator instanceof TestAuthenticator);
        $user = new TestUser($id, $roles);
        $testAuthenticator->setUser($user);

        $client->loginUser($user, 'api');

        return $client;
    }

    /**
     * @deprecated
     */
    public function getUser(KernelBrowser $client): UserInterface
    {
        $tokenStorage = $client->getContainer()->get(TokenStorageInterface::class);
        assert($tokenStorage instanceof TokenStorageInterface);

        return $tokenStorage->getToken()->getUser();
    }
}
