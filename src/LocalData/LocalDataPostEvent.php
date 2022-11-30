<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class LocalDataPostEvent extends Event implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var LocalDataAwareInterface */
    private $entity;

    /** @var array */
    private $sourceData;

    /** @var array */
    private $requestedAttributes;

    public function __construct(LocalDataAwareInterface $entity, array $sourceData)
    {
        $this->entity = $entity;
        $this->sourceData = $sourceData;
    }

    public function getSourceData(): array
    {
        return $this->sourceData;
    }

    public function getEntity(): LocalDataAwareInterface
    {
        return $this->entity;
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
        $this->setLocalDataAttributeInternal($key, $value, true);
    }

    /**
     * Tries to set a local data attribute of this event's entity and removes it from the list of requested attributes.
     *
     * @parem string $key The name of the attribute.
     *
     * @param mixed|null $value the value for the attribute
     */
    public function trySetLocalDataAttribute(string $key, $value): void
    {
        $this->setLocalDataAttributeInternal($key, $value, false);
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

    /**
     * Sets a local data attribute of this event's entity and removes it from the list of requested attributes.
     *
     * @parem string $key The name of the attribute.
     *
     * @param mixed|null $value the value for the attribute
     *
     * @throws ApiError if attribute $key is not in the set of requested attributes
     */
    private function setLocalDataAttributeInternal(string $key, $value, bool $warnfNotFound): void
    {
        $arrayKey = array_search($key, $this->requestedAttributes, true);
        if ($arrayKey === false) {
            if ($warnfNotFound) {
                if ($this->logger !== null) {
                    $this->logger->warning(sprintf("trying to set local data attribute '%s', which was not requested for entity '%s'", $key, LocalDataEventDispatcher::getUniqueEntityName(get_class($this->entity))));
                }
                assert(false);
            } else {
                return;
            }
        }

        // once set, remove the attribute from the list of requested attributes
        array_splice($this->requestedAttributes, $arrayKey, 1);
        $this->entity->setLocalDataValue($key, $value);
    }
}
