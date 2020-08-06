<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerAuthenticator;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use DBP\API\CoreBundle\TestUtils\DummyUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class KeycloakBearerAuthenticatorTest extends TestCase
{
    public function testSupportsRememberMe()
    {
        $auth = new KeycloakBearerAuthenticator();
        $this->assertFalse($auth->supportsRememberMe());
    }

    public function testGetUser()
    {
        $auth = new KeycloakBearerAuthenticator();

        $user = new KeycloakBearerUser('something', 'foobar', new DummyPersonProvider(new Person()), false, []);
        $provider = new DummyUserProvider($user);
        $credentials = ['token' => 'foobar'];
        $user = $auth->getUser($credentials, $provider);
        $this->assertNotNull($user);
    }

    public function testGetUserNoCred()
    {
        $auth = new KeycloakBearerAuthenticator();
        $user = new KeycloakBearerUser('something', 'foobar', new DummyPersonProvider(new Person()), false, []);
        $provider = new DummyUserProvider($user);
        $this->expectException(BadCredentialsException::class);
        $auth->getUser([], $provider);
    }
}
