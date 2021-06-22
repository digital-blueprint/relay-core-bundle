<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProvider;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use PHPUnit\Framework\TestCase;

class KeycloakBearerUserProviderTest extends TestCase
{
    public function testIsServiceAccountToken()
    {
        $this->assertTrue(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'foo bar']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'openid foo bar']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'openid']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'foo openid bar']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'foo bar openid']));
    }

    public function testCreateLoggingID()
    {
        $provider = new DummyPersonProvider(new Person());
        $auth = new KeycloakBearerUserProvider($provider);
        $id = $auth->createLoggingID([]);
        $this->assertSame('unknown-unknown', $id);
        $id = $auth->createLoggingID(['azp' => 'clientA', 'session_state' => 'state']);
        $this->assertSame('clientA-abfa50', $id);
    }
}
