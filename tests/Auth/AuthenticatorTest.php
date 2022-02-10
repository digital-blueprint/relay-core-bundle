<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Auth;

use Dbp\Relay\CoreBundle\Auth\AuthenticatorCompilerPass;
use Dbp\Relay\CoreBundle\Auth\ProxyAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\TestAuthenticator;
use Dbp\Relay\CoreBundle\TestUtils\TestUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class AuthenticatorTest extends TestCase
{
    public function testSupports()
    {
        $auth = new ProxyAuthenticator();
        $this->assertFalse($auth->supports(new Request()));
    }

    public function testSingle()
    {
        $auth = new ProxyAuthenticator();
        $user = new TestUser();
        $sub = new TestAuthenticator($user, 'bla');
        $auth->addAuthenticator($sub);
        $request = new Request();
        $request->headers->add(['Authorization' => 'Bearer bla']);
        $this->assertTrue($auth->supports($request));
        $passport = $auth->authenticate($request);
        $this->assertSame($passport->getUser(), $user);
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
