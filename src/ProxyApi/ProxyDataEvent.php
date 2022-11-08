<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

use Symfony\Contracts\EventDispatcher\Event;

class ProxyDataEvent extends Event
{
    public const NAME = 'dbp.relay.proxy_bundle.proxy_data';

    /** @var ProxyDataInterface */
    private $proxyData;

    /** @var bool */
    private $wasHandled;

    public function __construct(ProxyDataInterface $proxyData)
    {
        $this->proxyData = $proxyData;
        $this->wasHandled = false;
    }

    public function getProxyData(): ProxyDataInterface
    {
        return $this->proxyData;
    }

    /**
     * Indicate, that the event was handled, e.g. there was an event subscriber for the requested proxy data namespace.
     */
    public function setHandled(): void
    {
        $this->wasHandled = true;
    }

    /**
     * True, if the event was handled, e.g. there was an event subscriber for the requested proxy data namespace, false otherwise.
     */
    public function wasHandled(): bool
    {
        return $this->wasHandled;
    }
}
