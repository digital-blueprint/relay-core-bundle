<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\User;

use Dbp\Relay\CoreBundle\TestUtils\TestUserAttributeProvider;
use Dbp\Relay\CoreBundle\User\Event\GetAvailableUserAttributesEvent;
use Dbp\Relay\CoreBundle\User\Event\GetUserAttributeEvent;
use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UserAttributeMuxerTest extends TestCase
{
    /**
     * @throws UserAttributeException
     */
    public function testBasics()
    {
        $dummy = new TestUserAttributeProvider(['foo' => null, 'bar' => null]);
        $dummy->addUser('testuser', ['foo' => 42]);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), new EventDispatcher());
        $this->assertSame(42, $mux->getAttribute('testuser', 'foo'));
        $this->assertSame(24, $mux->getAttribute('testuser', 'bar', 24));
    }

    /**
     * @throws UserAttributeException
     */
    public function testUserAttributeUndefined()
    {
        $dummy = new TestUserAttributeProvider(['foo' => null, 'bar' => null]);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), new EventDispatcher());
        $this->expectException(UserAttributeException::class);
        $this->expectExceptionCode(UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
        $mux->getAttribute('testuser', 'baz');
    }

    /**
     * @throws UserAttributeException
     */
    public function testMultiple()
    {
        $dummy = new TestUserAttributeProvider(['foo' => null, 'qux' => null]);
        $dummy->addUser('testuser', ['foo' => 42]);
        $dummy2 = new TestUserAttributeProvider(['bar' => null, 'baz' => null]);
        $dummy2->addUser('testuser', ['bar' => 24]);

        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy, $dummy2]), new EventDispatcher());
        $this->assertSame(42, $mux->getAttribute('testuser', 'foo'));
        $this->assertSame('default', $mux->getAttribute('testuser', 'qux', 'default'));
        $this->assertSame(24, $mux->getAttribute('testuser', 'bar'));
        $this->assertSame(12, $mux->getAttribute('testuser', 'baz', 12));
        $this->assertNull($mux->getAttribute('testuser', 'baz'));
    }

    public function testAdditionallyDefinedAttributes()
    {
        $dummy = new TestUserAttributeProvider(['foo' => null, 'bar' => null]);
        $dispatched = new EventDispatcher();
        $getAvail = function (GetAvailableUserAttributesEvent $event) {
            $event->addAttributes(['new']);
        };
        $dispatched->addListener(GetAvailableUserAttributesEvent::class, $getAvail);
        $getAvail2 = function (GetAvailableUserAttributesEvent $event) {
            $event->addAttributes(['new2']);
        };
        $dispatched->addListener(GetAvailableUserAttributesEvent::class, $getAvail2);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), $dispatched);
        $this->assertNull($mux->getAttribute('myuser', 'foo'));
        $this->assertNull($mux->getAttribute('myuser', 'bar'));
        $this->assertNull($mux->getAttribute('myuser', 'new'));
        $this->assertNull($mux->getAttribute('myuser', 'new2'));
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetAttrEvent()
    {
        $dummy = new TestUserAttributeProvider(['foo' => null, 'bar' => null]);
        $dispatched = new EventDispatcher();
        $getAttr = function (GetUserAttributeEvent $event) {
            $this->assertSame('myuser', $event->getUserIdentifier());
            $this->assertSame('bar', $event->getAttributeName());
            $this->assertNull($event->getAttributeValue());
            $event->setAttributeValue('OK');
        };
        $dispatched->addListener(GetUserAttributeEvent::class, $getAttr);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), $dispatched);
        $this->assertSame('OK', $mux->getAttribute('myuser', 'bar'));
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetAttrEventDefault()
    {
        $dummy = new TestUserAttributeProvider([]);
        $dispatched = new EventDispatcher();
        $getAvail = function (GetAvailableUserAttributesEvent $event) {
            $event->addAttributes(['bar']);
        };
        $dispatched->addListener(GetAvailableUserAttributesEvent::class, $getAvail);
        $getAttr = function (GetUserAttributeEvent $event) {
            $this->assertNull($event->getAttributeValue());
        };
        $dispatched->addListener(GetUserAttributeEvent::class, $getAttr);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), $dispatched);
        $this->assertSame('Default', $mux->getAttribute('myuser', 'bar', 'Default'));
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetAttrMultipleEvents()
    {
        $dummy = new TestUserAttributeProvider(['foo' => null, 'bar' => null]);
        $dispatched = new EventDispatcher();
        $getAttr = function (GetUserAttributeEvent $event) {
            $event->setAttributeValue('OK');
        };
        $dispatched->addListener(GetUserAttributeEvent::class, $getAttr);
        $getAttr2 = function (GetUserAttributeEvent $event) {
            $this->assertSame('OK', $event->getAttributeValue());
            $event->setAttributeValue('OK2');
        };
        $dispatched->addListener(GetUserAttributeEvent::class, $getAttr2);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), $dispatched);
        $this->assertSame('OK2', $mux->getAttribute('myuser', 'bar'));
    }
}
