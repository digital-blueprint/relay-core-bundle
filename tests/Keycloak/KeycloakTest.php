<?php

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Keycloak\Keycloak;
use PHPUnit\Framework\TestCase;

class KeycloakTest extends TestCase
{
    public function testURLs()
    {
        $provider = new Keycloak('http://foo.bar', 'somerealm');

        $this->assertSame('http://foo.bar/realms/somerealm', $provider->getBaseUrlWithRealm());
        $this->assertSame('http://foo.bar/realms/somerealm/protocol/openid-connect/auth', $provider->getBaseAuthorizationUrl());
        $this->assertSame('http://foo.bar/realms/somerealm/protocol/openid-connect/token/introspect', $provider->getTokenIntrospectionUrl());
        $this->assertSame('http://foo.bar/realms/somerealm/protocol/openid-connect/token', $provider->getBaseAccessTokenUrl([]));
    }
}