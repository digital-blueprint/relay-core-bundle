<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Options;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class LocalDataEventDispatcher
{
    /** @var string */
    private $resourceClass;

    /** @var array */
    private $localDataAttributes = [];

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param string                   $resourceClass   The class name of the entity (resource) this event dispatcher is responsible for
     * @param EventDispatcherInterface $eventDispatcher The inner event dispatcher that this event dispatcher decorates
     */
    public function __construct(string $resourceClass, EventDispatcherInterface $eventDispatcher)
    {
        $this->resourceClass = $resourceClass;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * To be called at the beginning of a new operation.
     */
    public function onNewOperation(array $options): void
    {
        $this->localDataAttributes = Options::getLocalDataAttributes($options);
    }

    public function getRequestedAttributes(): array
    {
        return $this->localDataAttributes;
    }

    /**
     * Returns, whether the attribute with the given name was requested.
     */
    public function isAttributeRequested(string $attributeName): bool
    {
        return in_array($attributeName, $this->localDataAttributes, true);
    }

    /**
     * Checks if the given entity's local data attribute names matches the list of requested attributes this event dispatcher's entity (resource).
     * NOTE: The resource class of the entities must match.
     *
     * @param LocalDataAwareInterface $entity The entity whose local data attributes to check
     */
    public function checkRequestedAttributesIdentical(LocalDataAwareInterface $entity): bool
    {
        $availableAttributes = $entity->getLocalData() ? array_keys($entity->getLocalData()) : [];

        return count($this->localDataAttributes) === count($availableAttributes) &&
            empty(array_diff($this->localDataAttributes, $availableAttributes));
    }

    /**
     * Dispatches the given event.
     *
     * @throws ApiError
     */
    public function dispatch(Event $event, string $eventName = null): void
    {
        if ($event instanceof LocalDataPostEvent) {
            $event->initRequestedAttributes($this->localDataAttributes);
            $this->eventDispatcher->dispatch($event, $eventName);

            $pendingAttributes = $event->getPendingRequestedAttributes();
            if (count($pendingAttributes) !== 0) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('the following requested local data attributes could not be provided: %s', implode(', ', $pendingAttributes)));
            }
        } else {
            $this->eventDispatcher->dispatch($event, $eventName);
        }
    }
}
