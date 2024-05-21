<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Auth;

use Dbp\Relay\CoreBundle\Auth\AuthenticatorCompilerPass;
use Dbp\Relay\CoreBundle\Auth\ProxyAuthenticator;
use Dbp\Relay\CoreBundle\Auth\UserSession;
use Dbp\Relay\CoreBundle\TestUtils\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\TestUser;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

class AuthenticatorTest extends TestCase
{
    public function testSupports()
    {
        $auth = new ProxyAuthenticator(new UserSession());
        $this->assertFalse($auth->supports(new Request()));
    }

    public function testSingle()
    {
        $userIdentifier = 'userIdentifier';
        $user = new TestUser($userIdentifier);
        $this->assertSame($userIdentifier, $user->getUserIdentifier());

        $userSession = new UserSession();
        $proxyAuthenticator = new ProxyAuthenticator($userSession);
        $testAuthenticator = new TestAuthenticator(new TestUserSession());
        $testAuthenticator->setUser($user);
        $testAuthenticator->setToken('bla');
        $proxyAuthenticator->addAuthenticator($testAuthenticator);

        $request = new Request();
        $request->headers->add(['Authorization' => 'Bearer bla']);
        $this->assertTrue($proxyAuthenticator->supports($request));

        $passport = $proxyAuthenticator->authenticate($request);
        $this->assertSame($passport->getUser(), $user);

        $proxyAuthenticator->onAuthenticationSuccess($request, new NullToken(), 'firewall');
        $this->assertSame($userIdentifier, $userSession->getUserIdentifier());
    }

    public function testServiceAccount()
    {
        $user = new TestUser(null);

        $userSession = new UserSession();
        $proxyAuthenticator = new ProxyAuthenticator($userSession);
        $testAuthenticator = new TestAuthenticator(new TestUserSession());
        $testAuthenticator->setUser($user);
        $testAuthenticator->setToken('bla');
        $proxyAuthenticator->addAuthenticator($testAuthenticator);

        $request = new Request();
        $request->headers->add(['Authorization' => 'Bearer bla']);
        $this->assertTrue($proxyAuthenticator->supports($request));
        $passport = $proxyAuthenticator->authenticate($request);
        $this->assertSame($passport->getUser(), $user);
        $proxyAuthenticator->onAuthenticationSuccess($request, new NullToken(), 'firewall');
        $this->assertSame(null, $userSession->getUserIdentifier());
    }

    public function testCompilerPass()
    {
        $builder = new ContainerBuilder(new ParameterBag());
        $builder->register(ProxyAuthenticator::class);
        AuthenticatorCompilerPass::register($builder);
        $pass = new AuthenticatorCompilerPass();
        $pass->process($builder);
        $this->assertTrue(true);
    }
}
