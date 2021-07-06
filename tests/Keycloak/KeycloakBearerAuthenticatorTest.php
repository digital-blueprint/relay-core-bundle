<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerAuthenticator;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use DBP\API\CoreBundle\TestUtils\DummyUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class KeycloakBearerAuthenticatorTest extends ApiTestCase
{
    public function testAuthenticateNoHeader()
    {
        $user = new KeycloakBearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'nope');
        $auth = new KeycloakBearerAuthenticator($provider);

        $req = new Request();
        $this->expectException(BadCredentialsException::class);
        $auth->authenticate($req);
    }

    public function testSupports()
    {
        $user = new KeycloakBearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'bar');
        $auth = new KeycloakBearerAuthenticator($provider);

        $this->assertFalse($auth->supports(new Request()));

        $r = new Request();
        $r->headers->set('Authorization', 'foobar');
        $this->assertTrue($auth->supports($r));
    }
}
