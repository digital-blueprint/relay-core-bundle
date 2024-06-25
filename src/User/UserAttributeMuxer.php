<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

use Dbp\Relay\CoreBundle\User\Event\GetAvailableUserAttributesEvent;
use Dbp\Relay\CoreBundle\User\Event\GetUserAttributeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class UserAttributeMuxer
{
    /** @var iterable<UserAttributeProviderInterface> */
    private $userAttributeProviders;

    /** @var array<int, array> */
    private $providerCache;

    /** @var array<int, string[]> */
    private $availableCache;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var string[] */
    private $attributeStack;

    /**
     * @var ?string[]
     */
    private $availableCacheAll;

    public function __construct(UserAttributeProviderProviderInterface $userAttributeProviderProvider, EventDispatcherInterface $eventDispatcher)
    {
        $this->userAttributeProviders = $userAttributeProviderProvider->getAuthorizationDataProviders();
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
            foreach ($this->userAttributeProviders as $userAttributeProvider) {
                $availableAttributes = $this->getProviderAvailableAttributes($userAttributeProvider);
                $res = array_merge($res, $availableAttributes);
            }

            $event = new GetAvailableUserAttributesEvent($res);
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
    private function getProviderAvailableAttributes(UserAttributeProviderInterface $prov): array
    {
        // Caches getAvailableAttributes for each provider
        $provKey = spl_object_id($prov);
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
    private function getProviderUserAttributes(UserAttributeProviderInterface $userAttributeProvider, ?string $userIdentifier): array
    {
        // We cache the attributes for each provider, but only for the last userIdentifier
        $provKey = spl_object_id($userAttributeProvider);
        if (!array_key_exists($provKey, $this->providerCache) || $this->providerCache[$provKey][0] !== $userIdentifier) {
            $this->providerCache[$provKey] = [$userIdentifier, $userAttributeProvider->getUserAttributes($userIdentifier)];
        }
        $res = $this->providerCache[$provKey];
        assert($res[0] === $userIdentifier);

        return $res[1];
    }

    private function getProviderUserAttribute(UserAttributeProviderInterface $userAttributeProvider, ?string $userIdentifier, string $attributeName): mixed
    {
        if ($userAttributeProvider instanceof UserAttributeProviderExInterface) {
            return $userAttributeProvider->getUserAttribute($userIdentifier, $attributeName);
        } else {
            $userAttributes = $this->getProviderUserAttributes($userAttributeProvider, $userIdentifier);
            if (!array_key_exists($attributeName, $userAttributes)) {
                throw new UserAttributeException(sprintf('attribute \'%s\' was not provided', $attributeName), UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
            }

            return $userAttributes[$attributeName];
        }
    }

    /**
     * @param mixed $defaultValue
     *
     * @return mixed
     *
     * @throws UserAttributeException
     */
    public function getAttribute(?string $userIdentifier, string $attributeName, $defaultValue = null)
    {
        if (!in_array($attributeName, $this->getAvailableAttributes(), true)) {
            throw new UserAttributeException(sprintf('attribute \'%s\' undefined', $attributeName), UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
        }

        $value = null;
        foreach ($this->userAttributeProviders as $authorizationDataProvider) {
            $availableAttributes = $this->getProviderAvailableAttributes($authorizationDataProvider);
            if (!in_array($attributeName, $availableAttributes, true)) {
                continue;
            }
            $value = $this->getProviderUserAttribute($authorizationDataProvider, $userIdentifier, $attributeName);
            break;
        }

        $event = new GetUserAttributeEvent($this, $attributeName, $value, $userIdentifier);
        $event->setAttributeValue($value);

        // Prevent endless recursions by only emitting an event for each attribute only once
        if (in_array($attributeName, $this->attributeStack, true)) {
            throw new UserAttributeException(sprintf('infinite loop caused by a %s subscriber. authorization attribute: %s', GetUserAttributeEvent::class, $attributeName), UserAttributeException::INFINITE_EVENT_LOOP_DETECTED);
        }
        array_push($this->attributeStack, $attributeName);
        try {
            $this->eventDispatcher->dispatch($event);
        } finally {
            array_pop($this->attributeStack);
        }

        return $event->getAttributeValue() ?? $defaultValue;
    }
}
