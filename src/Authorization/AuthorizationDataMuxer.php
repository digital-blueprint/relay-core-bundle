<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Authorization\Event\GetAttributeEvent;
use Dbp\Relay\CoreBundle\Authorization\Event\GetAvailableAttributesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class AuthorizationDataMuxer
{
    /** @var iterable<AuthorizationDataProviderInterface> */
    private $authorizationDataProviders;

    /** @var array<string, array> */
    private $providerCache;

    /** @var array<string, string[]> */
    private $availableCache;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var string[] */
    private $attributeStack;

    /**
     * @var ?string[]
     */
    private $availableCacheAll;

    public function __construct(AuthorizationDataProviderProvider $authorizationDataProviderProvider, EventDispatcherInterface $eventDispatcher)
    {
        $this->authorizationDataProviders = $authorizationDataProviderProvider->getAuthorizationDataProviders();
        $this->eventDispatcher = $eventDispatcher;
        $this->providerCache = [];
        $this->availableCache = [];
        $this->availableCacheAll = null;
        $this->attributeStack = [];
    }

    /**
     * Returns an array of available attributes.
     *
     * @return string[]
     */
    public function getAvailableAttributes(): array
    {
        if ($this->availableCacheAll === null) {
            $res = [];
            foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
                $availableAttributes = $this->getProviderAvailableAttributes($authorizationDataProvider);
                $res = array_merge($res, $availableAttributes);
            }

            $event = new GetAvailableAttributesEvent($res);
            $this->eventDispatcher->dispatch($event);
            $this->availableCacheAll = $event->getAttributes();
        }

        return $this->availableCacheAll;
    }

    /**
     * Returns a cached list for available attributes for the provider.
     *
     * @return string[]
     */
    private function getProviderAvailableAttributes(AuthorizationDataProviderInterface $prov): array
    {
        // Caches getAvailableAttributes for each provider
        $provKey = get_class($prov);
        if (!array_key_exists($provKey, $this->availableCache)) {
            $this->availableCache[$provKey] = $prov->getAvailableAttributes();
        }

        return $this->availableCache[$provKey];
    }

    /**
     * Returns a cached map of available user attributes.
     *
     * @return array<string, mixed>
     */
    private function getProviderUserAttributes(AuthorizationDataProviderInterface $prov, ?string $userIdentifier): array
    {
        // We cache the attributes for each provider, but only for the last userIdentifier
        $provKey = get_class($prov);
        if (!array_key_exists($provKey, $this->providerCache) || $this->providerCache[$provKey][0] !== $userIdentifier) {
            $this->providerCache[$provKey] = [$userIdentifier, $prov->getUserAttributes($userIdentifier)];
        }
        $res = $this->providerCache[$provKey];
        assert($res[0] === $userIdentifier);

        return $res[1];
    }

    /**
     * @param mixed $defaultValue
     *
     * @return mixed
     *
     * @throws AuthorizationException
     */
    public function getAttribute(?string $userIdentifier, string $attributeName, $defaultValue = null)
    {
        if (!in_array($attributeName, $this->getAvailableAttributes(), true)) {
            throw new AuthorizationException(sprintf('attribute \'%s\' undefined', $attributeName), AuthorizationException::ATTRIBUTE_UNDEFINED);
        }

        $value = $defaultValue;
        foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
            $availableAttributes = $this->getProviderAvailableAttributes($authorizationDataProvider);
            if (!in_array($attributeName, $availableAttributes, true)) {
                continue;
            }
            $userAttributes = $this->getProviderUserAttributes($authorizationDataProvider, $userIdentifier);
            if (!array_key_exists($attributeName, $userAttributes)) {
                continue;
            }
            $value = $userAttributes[$attributeName];
            break;
        }

        $event = new GetAttributeEvent($this, $attributeName, $value, $userIdentifier);
        $event->setAttributeValue($value);

        // Prevent endless recursions by only emitting an event for each attribute only once
        if (in_array($attributeName, $this->attributeStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by a %s subscriber. authorization attribute: %s', GetAttributeEvent::class, $attributeName), AuthorizationException::INFINITE_EVENT_LOOP_DETECTED);
        }
        array_push($this->attributeStack, $attributeName);
        try {
            $this->eventDispatcher->dispatch($event);
        } finally {
            array_pop($this->attributeStack);
        }

        return $event->getAttributeValue();
    }
}
