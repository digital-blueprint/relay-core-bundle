<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Service;

use DBP\API\CoreBundle\Service\DefaultUserSession;
use PHPUnit\Framework\TestCase;

class DefaultUserDataProviderTest extends TestCase
{
    public function testIsServiceAccountToken()
    {
        $this->assertTrue(DefaultUserSession::isServiceAccountToken(['scope' => 'foo bar']));
        $this->assertFalse(DefaultUserSession::isServiceAccountToken(['scope' => 'openid foo bar']));
        $this->assertFalse(DefaultUserSession::isServiceAccountToken(['scope' => 'openid']));
        $this->assertFalse(DefaultUserSession::isServiceAccountToken(['scope' => 'foo openid bar']));
        $this->assertFalse(DefaultUserSession::isServiceAccountToken(['scope' => 'foo bar openid']));
    }

    public function testGetLoggingId()
    {
        $prov = new DefaultUserSession();
        $prov->setSessionToken([]);
        $this->assertSame('unknown-unknown', $prov->getSessionLoggingId());
        $prov->setSessionToken(['azp' => 'clientA', 'session_state' => 'state']);
        $this->assertSame('clientA-abfa50', $prov->getSessionLoggingId());
    }
}
