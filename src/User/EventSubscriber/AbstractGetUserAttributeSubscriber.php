<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\EventSubscriber;

use Dbp\Relay\CoreBundle\User\Event\GetAvailableUserAttributesEvent;
use Dbp\Relay\CoreBundle\User\Event\GetUserAttributeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractGetUserAttributeSubscriber implements EventSubscriberInterface
{
    private array $eventStack = [];

    public static function getSubscribedEvents(): array
    {
        return [
            GetAvailableUserAttributesEvent::class => 'onGetAvailableUserAttributes',
            GetUserAttributeEvent::class => 'onGetUserAttributeEvent',
        ];
    }

    public function onGetAvailableUserAttributes(GetAvailableUserAttributesEvent $event): void
    {
        $event->addAttributes($this->getNewAttributes());
    }

    public function onGetUserAttributeEvent(GetUserAttributeEvent $event): void
    {
        try {
            array_push($this->eventStack, $event);
            $attributeName = $event->getAttributeName();

            $event->setAttributeValue(in_array($attributeName, $this->getNewAttributes(), true) ?
                $this->getNewAttributeValue($event->getUserIdentifier(), $attributeName, $event->getAttributeValue()) :
                $this->updateExistingAttributeValue($event->getUserIdentifier(), $attributeName, $event->getAttributeValue())
            );
        } finally {
            array_pop($this->eventStack);
        }
    }

    public function getAttribute(string $attributeName, mixed $defaultValue = null): mixed
    {
        return $this->eventStack[array_key_last($this->eventStack)]->getAttribute($attributeName, $defaultValue);
    }

    protected function updateExistingAttributeValue(?string $userIdentifier, string $attributeName, mixed $attributeValue): mixed
    {
        return $attributeValue;
    }

    /*
     * @return string[] The array of new attribute names that this subscriber provides
     */
    abstract protected function getNewAttributes(): array;

    abstract protected function getNewAttributeValue(?string $userIdentifier, string $attributeName, mixed $defaultValue): mixed;
}
