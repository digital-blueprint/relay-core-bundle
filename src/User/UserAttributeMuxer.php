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
    private iterable $userAttributeProviders;

    /** @var array<string, mixed> */
    private array $valueCache = [];

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
        $this->userAttributeProviders = $userAttributeProviderProvider->getAuthorizationDataProviders();
    }

    /**
     * Clears all caches. For testing purposes.
     */
    public function clearCaches(): void
    {
        $this->additionallyAvailableAttributesCache = null;
        $this->valueCache = [];
    }

    /**
     * @throws UserAttributeException
     */
    public function getAttribute(?string $userIdentifier, string $attributeName, mixed $defaultValue = null): mixed
    {
        $valueKey = md5($userIdentifier.$attributeName);

        if (isset($this->valueCache[$valueKey])) {
            $value = $this->valueCache[$valueKey];
        } else {
            $value = null;
            if (false === in_array($attributeName, $this->getAdditionallyAvailableAttributesCached(), true)) {
                $found = false;
                foreach ($this->userAttributeProviders as $userAttributeItemProvider) {
                    if ($userAttributeItemProvider->hasUserAttribute($attributeName)) {
                        $value = $userAttributeItemProvider->getUserAttribute($userIdentifier, $attributeName);
                        $found = true;
                        break;
                    }
                }
                if (false === $found) {
                    throw new UserAttributeException(sprintf('attribute \'%s\' undefined', $attributeName),
                        UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
                }
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

            $this->valueCache[$valueKey] = $value = $event->getAttributeValue();
        }

        return $value ?? $defaultValue;
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
