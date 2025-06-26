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
    /** @var UserAttributeCollectionProviderInterface[] */
    private array $userAttributeCollectionProviders = [];

    /** @var UserAttributeItemProviderInterface[] */
    private array $userAttributeItemProviders = [];

    /** @var array<int, array> */
    private array $valuesPerCollectionProviderCache = [];

    /** @var array<int, string[]> */
    private array $availableAttributesPerCollectionProviderCache = [];

    /** @var string[] */
    private array $attributeStack = [];

    /**
     * @var ?string[]
     */
    private ?array $additionallyAvailableAttributesCache = null;

    public function __construct(
        UserAttributeProviderProviderInterface $userAttributeProviderProvider,
        private readonly EventDispatcherInterface $eventDispatcher)
    {
        foreach ($userAttributeProviderProvider->getAuthorizationDataProviders() as $userAttributeProvider) {
            if ($userAttributeProvider instanceof UserAttributeCollectionProviderInterface) {
                $this->userAttributeCollectionProviders[] = $userAttributeProvider;
            } elseif ($userAttributeProvider instanceof UserAttributeItemProviderInterface) {
                $this->userAttributeItemProviders[] = $userAttributeProvider;
            }
        }
    }

    /**
     * Clears all caches. For testing purposes.
     */
    public function clearCaches(): void
    {
        $this->additionallyAvailableAttributesCache = null;
        $this->valuesPerCollectionProviderCache = [];
        $this->availableAttributesPerCollectionProviderCache = [];
    }

    /**
     * Returns an array of available attributes.
     *
     * @return string[]
     */
    public function getAvailableAttributes(): array
    {
        $availableAttributes = $this->getAdditionallyAvailableAttributesCached();

        foreach ($this->userAttributeCollectionProviders as $userAttributeProvider) {
            $availableAttributes = array_merge($availableAttributes,
                $this->getProviderAvailableAttributesCached($userAttributeProvider));
        }

        return $availableAttributes;
    }

    /**
     * @throws UserAttributeException
     */
    public function getAttribute(?string $userIdentifier, string $attributeName, mixed $defaultValue = null): mixed
    {
        $value = null;
        $found = false;

        if (in_array($attributeName, $this->getAdditionallyAvailableAttributesCached(), true)) {
            $found = true;
        } else {
            foreach ($this->userAttributeCollectionProviders as $userAttributeCollectionProvider) {
                if (in_array($attributeName,
                    $this->getProviderAvailableAttributesCached($userAttributeCollectionProvider), true)) {
                    $value = $this->getProviderUserAttribute($userAttributeCollectionProvider, $userIdentifier, $attributeName);
                    $found = true;
                    break;
                }
            }

            if (false === $found) {
                foreach ($this->userAttributeItemProviders as $userAttributeItemProvider) {
                    if ($userAttributeItemProvider->hasUserAttribute($attributeName)) {
                        $value = $userAttributeItemProvider->getUserAttribute($userIdentifier, $attributeName);
                        $found = true;
                        break;
                    }
                }
            }
        }

        if (false === $found) {
            throw new UserAttributeException(sprintf('attribute \'%s\' undefined', $attributeName),
                UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
        }

        $event = new GetUserAttributeEvent($this, $attributeName, $value, $userIdentifier);
        $event->setAttributeValue($value);

        // Prevent endless recursions by only emitting an event for each attribute only once
        if (in_array($attributeName, $this->attributeStack, true)) {
            throw new UserAttributeException(sprintf('infinite loop caused by a %s subscriber. authorization attribute: %s',
                GetUserAttributeEvent::class, $attributeName), UserAttributeException::INFINITE_EVENT_LOOP_DETECTED);
        }
        $this->attributeStack[] = $attributeName;
        try {
            $this->eventDispatcher->dispatch($event);
        } finally {
            array_pop($this->attributeStack);
        }

        return $event->getAttributeValue() ?? $defaultValue;
    }

    /**
     * Returns a cached list for available attributes for the provider.
     *
     * @return string[]
     */
    private function getProviderAvailableAttributesCached(
        UserAttributeCollectionProviderInterface $userAttributeCollectionProvider): array
    {
        // Caches getAvailableAttributes for each provider
        $providerKey = spl_object_id($userAttributeCollectionProvider);
        if (false === array_key_exists($providerKey, $this->availableAttributesPerCollectionProviderCache)) {
            $this->availableAttributesPerCollectionProviderCache[$providerKey] = $userAttributeCollectionProvider->getAvailableAttributes();
        }

        return $this->availableAttributesPerCollectionProviderCache[$providerKey];
    }

    /**
     * Returns a cached map of available user attributes.
     *
     * @return array<string, mixed>
     */
    private function getProviderUserAttributes(
        UserAttributeCollectionProviderInterface $userAttributeProvider, ?string $userIdentifier): array
    {
        // We cache the attributes for each provider, but only for the last userIdentifier
        $providerKey = spl_object_id($userAttributeProvider);
        if (false === array_key_exists($providerKey, $this->valuesPerCollectionProviderCache)
            || $this->valuesPerCollectionProviderCache[$providerKey][0] !== $userIdentifier) {
            $this->valuesPerCollectionProviderCache[$providerKey] = [$userIdentifier, $userAttributeProvider->getUserAttributes($userIdentifier)];
        }
        $cacheEntry = $this->valuesPerCollectionProviderCache[$providerKey];
        assert($cacheEntry[0] === $userIdentifier);

        return $cacheEntry[1];
    }

    /**
     * @throws UserAttributeException
     */
    private function getProviderUserAttribute(
        UserAttributeCollectionProviderInterface $allAvailableUserAttributesProvider,
        ?string $userIdentifier, string $attributeName): mixed
    {
        $userAttributes = $this->getProviderUserAttributes($allAvailableUserAttributesProvider, $userIdentifier);
        if (false === array_key_exists($attributeName, $userAttributes)) {
            throw new UserAttributeException(sprintf('attribute \'%s\' was not provided', $attributeName),
                UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
        }

        return $userAttributes[$attributeName];
    }

    /**
     * @return string[]
     */
    private function getAdditionallyAvailableAttributesCached(): array
    {
        if ($this->additionallyAvailableAttributesCache === null) {
            $event = new GetAvailableUserAttributesEvent();
            $this->eventDispatcher->dispatch($event);
            $this->additionallyAvailableAttributesCache = $event->getAttributes();
        }

        return $this->additionallyAvailableAttributesCache;
    }
}
