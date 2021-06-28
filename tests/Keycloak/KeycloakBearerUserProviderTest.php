<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProvider;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use DBP\API\CoreBundle\TestUtils\DummyUserSession;

class KeycloakBearerUserProviderTest extends ApiTestCase
{
    public function testWithIdentifier()
    {
        $person = new Person();
        $person->setRoles(['quux']);
        $perprov = new DummyPersonProvider($person);

        $udprov = new DummyUserSession('foo', ['role']);
        $prov = new KeycloakBearerUserProvider($perprov, $udprov);
        $user = $prov->loadUserByValidatedToken([]);
        $this->assertSame('foo', $user->getUserIdentifier());
        $this->assertSame(['quux', 'role'], $user->getRoles());
    }

    public function testWithoutIdentifier()
    {
        $person = new Person();
        $person->setRoles(['quux']);
        $perprov = new DummyPersonProvider($person);

        $udprov = new DummyUserSession(null, ['role']);
        $prov = new KeycloakBearerUserProvider($perprov, $udprov);
        $user = $prov->loadUserByValidatedToken([]);
        $this->assertSame('', $user->getUserIdentifier());
        $this->assertSame(['role'], $user->getRoles());
    }
}
