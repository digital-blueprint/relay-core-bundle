<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use PHPUnit\Framework\TestCase;

class KeycloakBearerUserTest extends TestCase
{
    public function testRolesWithNoRealUser()
    {
        $user = new KeycloakBearerUser(null, ['foobar']);
        $this->assertSame(['foobar'], $user->getRoles());
    }

    public function testGetUserIdentifier()
    {
        $user = new KeycloakBearerUser(null, ['foobar']);
        $this->assertSame('', $user->getUserIdentifier());
        $this->assertSame('', $user->getUsername());
        $user = new KeycloakBearerUser('quux', ['foobar']);
        $this->assertSame('quux', $user->getUserIdentifier());
        $this->assertSame('quux', $user->getUsername());
    }
}
