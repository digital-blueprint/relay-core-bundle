<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Contracts\EventDispatcher\Event;

class LocalDataAwareEvent extends Event
{
    /** @var LocalDataAwareInterface */
    private $entity;

    /** @var array */
    private $requestedAttributes;

    protected function __construct(LocalDataAwareInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Sets the list of requested local data attribute names for this event's entity.
     *
     * @param string[] $requestedAttributes
     */
    public function setRequestedAttributes(array $requestedAttributes): void
    {
        $this->requestedAttributes = $requestedAttributes;
    }

    /**
     * Returns the list of local data attributes names that were not yet set for this event's entity.
     *
     * @retrun string[]
     */
    public function getRemainingRequestedAttributes(): array
    {
        return $this->requestedAttributes;
    }

    /**
     * Sets a local data attribute of this event's entity and removes it from the list of requested attributes.
     *
     * @parem string $key The name of the attribute.
     *
     * @param mixed|null $value the value for the attribute
     *
     * @throws ApiError if attribute $key is not in the set of requested attributes
     */
    public function setLocalDataAttribute(string $key, $value): void
    {
        $arrayKey = array_search($key, $this->requestedAttributes, true);
        if ($arrayKey === false) {
            // TODO: maybe ignore or just emit warning?
            throw new ApiError(500, sprintf("trying to set local data attribute '%s', which was not requested for entity '%s'", $key, LocalDataAwareEventDispatcher::getUniqueEntityName(get_class($this->entity))));
        }

        // once set, remove the attribute from the list of requested attributes
        array_splice($this->requestedAttributes, $arrayKey, 1);
        $this->entity->setLocalDataValue($key, $value);
    }

    /**
     * Returns, whether a given attribute was requested for this event's entity.
     *
     * @parem string $key The name of the attribute.
     */
    public function isLocalDataAttributeRequested(string $key): bool
    {
        return in_array($key, $this->requestedAttributes, true);
    }
}
