<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\User;

use Dbp\Relay\CoreBundle\TestUtils\TestUserAttributeProvider;
use Dbp\Relay\CoreBundle\User\UserAttributeException;
use PHPUnit\Framework\TestCase;

class TestUserAttributeProviderTest extends TestCase
{
    public function testHasUserAttribute()
    {
        $provider = new TestUserAttributeProvider();
        $this->assertFalse($provider->hasUserAttribute('foo'));

        $provider = new TestUserAttributeProvider(['bar' => '42']);
        $this->assertTrue($provider->hasUserAttribute('bar'));
    }

    public function testGetUserAttributeNoExit()
    {
        $provider = new TestUserAttributeProvider();
        $this->expectException(UserAttributeException::class);
        $provider->getUserAttribute('bla', 'foo');
    }

    public function testGetUserAttribute()
    {
        $provider = new TestUserAttributeProvider(['bar' => '42']);
        $this->assertSame('42', $provider->getUserAttribute('bla', 'bar'));
    }
}
