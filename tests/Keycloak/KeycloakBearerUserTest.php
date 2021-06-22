<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use PHPUnit\Framework\TestCase;

class KeycloakBearerUserTest extends TestCase
{
    public function testRolesWithNoRealUser()
    {
        $user = new KeycloakBearerUser(null, 'foobar', new DummyPersonProvider(new Person()), []);
        $this->assertSame([], $user->getRoles());

        $user = new KeycloakBearerUser(null, 'foobar', new DummyPersonProvider(new Person()), ['some']);
        $this->assertSame(['ROLE_SCOPE_SOME'], $user->getRoles());
    }
}
