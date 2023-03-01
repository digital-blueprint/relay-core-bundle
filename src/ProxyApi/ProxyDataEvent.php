<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

use Symfony\Contracts\EventDispatcher\Event;

class ProxyDataEvent extends Event
{
    /** @var ProxyDataInterface */
    private $proxyData;

    /** @var bool */
    private $wasAcknowledged;

    public function __construct(ProxyDataInterface $proxyData)
    {
        $this->proxyData = $proxyData;
        $this->wasAcknowledged = false;
    }

    public function getProxyData(): ProxyDataInterface
    {
        return $this->proxyData;
    }

    /**
     * Acknowledge the event, i.e. tell that there was an event subscriber responsible for the requested proxy data namespace.
     */
    public function acknowledge(): void
    {
        $this->wasAcknowledged = true;
    }

    /**
     * True, if the event was acknowledged, e.g. there was an event subscriber responsible for the requested proxy data namespace, false otherwise.
     */
    public function wasAcknowledged(): bool
    {
        return $this->wasAcknowledged;
    }
}
