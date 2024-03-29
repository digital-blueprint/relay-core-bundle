<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\EventSubscriber;

use Dbp\Relay\CoreBundle\User\Event\GetAvailableUserAttributesEvent;
use Dbp\Relay\CoreBundle\User\Event\GetUserAttributeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractGetUserAttributeSubscriber implements EventSubscriberInterface
{
    /** @var array */
    private $eventStack = [];

    public static function getSubscribedEvents(): array
    {
        return [
            GetAvailableUserAttributesEvent::class => 'onGetAvailableUserAttributes',
            GetUserAttributeEvent::class => 'onGetUserAttributeEvent',
        ];
    }

    public function onGetAvailableUserAttributes(GetAvailableUserAttributesEvent $event)
    {
        $event->addAttributes($this->getNewAttributes());
    }

    public function onGetUserAttributeEvent(GetUserAttributeEvent $event)
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

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->eventStack[array_key_last($this->eventStack)]->getAttribute($attributeName, $defaultValue);
    }

    /**
     * @param mixed|null $attributeValue The current attribute value
     *
     * @return mixed|null The updated attribute value
     */
    protected function updateExistingAttributeValue(?string $userIdentifier, string $attributeName, $attributeValue)
    {
        return $attributeValue;
    }

    /*
     * @return string[] The array of new attribute names that this subscriber provides
     */
    abstract protected function getNewAttributes(): array;

    /**
     * @param mixed|null $defaultValue the default value if provided explicitly in the authorization expression, else null
     *
     * @return mixed|null the value for the new attribute with the given name for the given user
     */
    abstract protected function getNewAttributeValue(?string $userIdentifier, string $attributeName, $defaultValue);
}
