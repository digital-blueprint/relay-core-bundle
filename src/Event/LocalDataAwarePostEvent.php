<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Event;

use Dbp\Relay\CoreBundle\Entity\LocalDataAwareInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\EventDispatcher\Event;

class LocalDataAwarePostEvent extends Event
{
    /** @var LocalDataAwareInterface */
    private $entity;

    /** @var array */
    private $requestedAttributes;

    protected function __construct(LocalDataAwareInterface $entity)
    {
        $this->entity = $entity;
    }

    public function setRequestedAttributes(array $requestedAttributes)
    {
        $this->requestedAttributes = $requestedAttributes;
    }

    public function getRemainingRequestedAttributes(): array
    {
        return $this->requestedAttributes;
    }

    public function getEntity(): LocalDataAwareInterface
    {
        return $this->entity;
    }

    public function setLocalDataAttribute(string $key, $value)
    {
        $arrayKey = array_search($key, $this->requestedAttributes, true);
        if ($arrayKey === false) {
            throw new HttpException(500, sprintf("local data attribute '%s' not requested for entity '%s'", $key, $this->entity->getUniqueEntityName()));
        }

        // once set, remove the attribute from the list of requested attributes
        array_splice($this->requestedAttributes, $arrayKey, 1);
        $this->entity->setLocalDataValue($key, $value);
    }

    public function isLocalDataAttributeRequested(string $key): bool
    {
        return in_array($key, $this->requestedAttributes, true);
    }
}
