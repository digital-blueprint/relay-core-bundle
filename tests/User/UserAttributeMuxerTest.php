<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\User;

use Dbp\Relay\CoreBundle\TestUtils\DummyUserAttributeProvider;
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
        $dummy = new DummyUserAttributeProvider(['foo' => 42], ['foo', 'bar']);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), new EventDispatcher());
        $this->assertSame(['foo', 'bar'], $mux->getAvailableAttributes());
        $this->assertSame(42, $mux->getAttribute(null, 'foo'));
        $this->assertSame(24, $mux->getAttribute(null, 'bar', 24));
    }

    /**
     * @throws UserAttributeException
     */
    public function testMultiple()
    {
        $dummy = new DummyUserAttributeProvider(['foo' => 42], ['foo', 'qux']);
        $dummy2 = new DummyUserAttributeProvider(['bar' => 24], ['bar', 'baz']);

        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy, $dummy2]), new EventDispatcher());
        $this->assertSame(['foo', 'qux', 'bar', 'baz'], $mux->getAvailableAttributes());
        $this->assertSame(42, $mux->getAttribute(null, 'foo'));
        $this->assertSame('default', $mux->getAttribute(null, 'qux', 'default'));
        $this->assertSame(24, $mux->getAttribute(null, 'bar'));
        $this->assertSame(12, $mux->getAttribute(null, 'baz', 12));
        $this->assertNull($mux->getAttribute(null, 'baz'));
    }

    public function testAvailEvent()
    {
        $dummy = new DummyUserAttributeProvider(['foo' => 42], ['foo', 'bar']);
        $dispatched = new EventDispatcher();
        $getAvail = function (GetAvailableUserAttributesEvent $event) {
            $this->assertSame(['foo', 'bar'], $event->getAttributes());
            $event->addAttributes(['new']);
        };
        $dispatched->addListener(GetAvailableUserAttributesEvent::class, $getAvail);
        $getAvail2 = function (GetAvailableUserAttributesEvent $event) {
            $event->addAttributes(['new2']);
        };
        $dispatched->addListener(GetAvailableUserAttributesEvent::class, $getAvail2);
        $mux = new UserAttributeMuxer(new UserAttributeProviderProvider([$dummy]), $dispatched);
        $this->assertSame(['foo', 'bar', 'new', 'new2'], $mux->getAvailableAttributes());
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetAttrEvent()
    {
        $dummy = new DummyUserAttributeProvider(['foo' => 42], ['foo', 'bar']);
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
        $dummy = new DummyUserAttributeProvider([], []);
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
        $dummy = new DummyUserAttributeProvider(['foo' => 42], ['foo', 'bar']);
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
