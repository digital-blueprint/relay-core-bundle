<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerAuthenticator;
use DBP\API\CoreBundle\TestUtils\DummyUserProvider;
use DBP\API\CoreBundle\TestUtils\UserAuthTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class KeycloakBearerAuthenticatorTest extends ApiTestCase
{
    use UserAuthTrait;

    public function testAuthenticateNoHeader()
    {
        [$client, $user] = $this->withUser('foo', 'bar');
        $provider = new DummyUserProvider($user, 'nope');
        $auth = new KeycloakBearerAuthenticator($provider);

        $req = new Request();
        $this->expectException(BadCredentialsException::class);
        $auth->authenticate($req);
    }

    public function testSupports()
    {
        [$client, $user] = $this->withUser('foo', 'bar');
        $provider = new DummyUserProvider($user, 'bar');
        $auth = new KeycloakBearerAuthenticator($provider);

        $this->assertFalse($auth->supports(new Request()));

        $r = new Request();
        $r->headers->set('Authorization', 'foobar');
        $this->assertTrue($auth->supports($r));
    }
}
