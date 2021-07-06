<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProvider;
use DBP\API\CoreBundle\TestUtils\DummyUserSession;

class KeycloakBearerUserProviderTest extends ApiTestCase
{
    public function testWithIdentifier()
    {
        $udprov = new DummyUserSession('foo', ['role']);
        $prov = new KeycloakBearerUserProvider($udprov);
        $user = $prov->loadUserByValidatedToken([]);
        $this->assertSame('foo', $user->getUserIdentifier());
        $this->assertSame(['role'], $user->getRoles());
    }

    public function testWithoutIdentifier()
    {
        $udprov = new DummyUserSession(null, ['role']);
        $prov = new KeycloakBearerUserProvider($udprov);
        $user = $prov->loadUserByValidatedToken([]);
        $this->assertSame('', $user->getUserIdentifier());
        $this->assertSame(['role'], $user->getRoles());
    }
}
