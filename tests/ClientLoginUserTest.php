<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ClientLoginUserTest extends AbstractApiTest
{
    public function testClientLoginUser()
    {
        $this->testClient->setUpUser('foo', symfonyRoles: ['bar']);

        $container = $this->testClient->getContainer();
        $testAuthenticator = $container->get(TestAuthenticator::class);
        assert($testAuthenticator instanceof TestAuthenticator);
        $this->testClient->getClient()->loginUser($testAuthenticator->getUser(), 'api');

        $tokenStorage = $container->get(TokenStorageInterface::class);
        assert($tokenStorage instanceof TokenStorageInterface);
        $user = $tokenStorage->getToken()->getUser();

        $this->assertSame(['bar'], $user->getRoles());
        $this->assertSame('foo', $user->getUserIdentifier());
    }
}
