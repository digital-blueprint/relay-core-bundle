<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Dbp\Relay\CoreBundle\Authorization\Event\GetAttributeEvent;
use Dbp\Relay\CoreBundle\Authorization\Event\GetAvailableAttributesEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuthorizationDataMuxerTest extends TestCase
{
    public function testBasics()
    {
        $dummy = new DummyAuthorizationDataProvider(['foo' => 42], ['foo', 'bar']);
        $mux = new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([$dummy]), new EventDispatcher());
        $this->assertSame(['foo', 'bar'], $mux->getAvailableAttributes());
        $this->assertSame(42, $mux->getAttribute(null, 'foo'));
        $this->assertSame(24, $mux->getAttribute(null, 'bar', 24));
    }

    public function testMultiple()
    {
        $dummy = new DummyAuthorizationDataProvider(['foo' => 42], ['foo', 'qux']);
        $dummy2 = new DummyAuthorizationDataProvider(['bar' => 24], ['bar', 'baz']);

        $mux = new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([$dummy, $dummy2]), new EventDispatcher());
        $this->assertSame(['foo', 'qux', 'bar', 'baz'], $mux->getAvailableAttributes());
        $this->assertSame(42, $mux->getAttribute(null, 'foo'));
        $this->assertSame('default', $mux->getAttribute(null, 'qux', 'default'));
        $this->assertSame(24, $mux->getAttribute(null, 'bar'));
        $this->assertSame(12, $mux->getAttribute(null, 'baz', 12));
        $this->assertNull($mux->getAttribute(null, 'baz'));
    }

    public function testAvailEvent()
    {
        $dummy = new DummyAuthorizationDataProvider(['foo' => 42], ['foo', 'bar']);
        $dispatched = new EventDispatcher();
        $getAvail = function (GetAvailableAttributesEvent $event) {
            $this->assertSame(['foo', 'bar'], $event->getAttributes());
            $event->addAttributes(['new']);
        };
        $dispatched->addListener(GetAvailableAttributesEvent::class, $getAvail);
        $getAvail2 = function (GetAvailableAttributesEvent $event) {
            $event->addAttributes(['new2']);
        };
        $dispatched->addListener(GetAvailableAttributesEvent::class, $getAvail2);
        $mux = new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([$dummy]), $dispatched);
        $this->assertSame(['foo', 'bar', 'new', 'new2'], $mux->getAvailableAttributes());
    }

    public function testGetAttrEvent()
    {
        $dummy = new DummyAuthorizationDataProvider(['foo' => 42], ['foo', 'bar']);
        $dispatched = new EventDispatcher();
        $getAttr = function (GetAttributeEvent $event) {
            $this->assertSame('myuser', $event->getUserIdentifier());
            $this->assertSame('bar', $event->getAttributeName());
            $this->assertNull($event->getAttributeValue());
            $event->setAttributeValue('OK');
        };
        $dispatched->addListener(GetAttributeEvent::class, $getAttr);
        $mux = new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([$dummy]), $dispatched);
        $this->assertSame('OK', $mux->getAttribute('myuser', 'bar'));
    }

    public function testGetAttrEventDefault()
    {
        $dummy = new DummyAuthorizationDataProvider([], []);
        $dispatched = new EventDispatcher();
        $getAvail = function (GetAvailableAttributesEvent $event) {
            $event->addAttributes(['bar']);
        };
        $dispatched->addListener(GetAvailableAttributesEvent::class, $getAvail);
        $getAttr = function (GetAttributeEvent $event) {
            $this->assertNull($event->getAttributeValue());
        };
        $dispatched->addListener(GetAttributeEvent::class, $getAttr);
        $mux = new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([$dummy]), $dispatched);
        $this->assertSame('Default', $mux->getAttribute('myuser', 'bar', 'Default'));
    }

    public function testGetAttrMultipleEvents()
    {
        $dummy = new DummyAuthorizationDataProvider(['foo' => 42], ['foo', 'bar']);
        $dispatched = new EventDispatcher();
        $getAttr = function (GetAttributeEvent $event) {
            $event->setAttributeValue('OK');
        };
        $dispatched->addListener(GetAttributeEvent::class, $getAttr);
        $getAttr2 = function (GetAttributeEvent $event) {
            $this->assertSame('OK', $event->getAttributeValue());
            $event->setAttributeValue('OK2');
        };
        $dispatched->addListener(GetAttributeEvent::class, $getAttr2);
        $mux = new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([$dummy]), $dispatched);
        $this->assertSame('OK2', $mux->getAttribute('myuser', 'bar'));
    }
}
