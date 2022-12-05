<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\EventSubscriber;

use Dbp\Relay\CoreBundle\Authorization\Event\GetAttributeEvent;
use Dbp\Relay\CoreBundle\Authorization\Event\GetAvailableAttributesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractGetAttributeSubscriber implements EventSubscriberInterface
{
    /** @var GetAttributeEvent|null */
    private $event;

    public static function getSubscribedEvents(): array
    {
        return [
            GetAvailableAttributesEvent::class => 'onGetAvailableAttributes',
            GetAttributeEvent::class => 'onGetAttributeEvent',
        ];
    }

    public function onGetAvailableAttributes(GetAvailableAttributesEvent $event)
    {
        $event->addAttributes($this->getAvailableAttributes());
    }

    public function onGetAttributeEvent(GetAttributeEvent $event)
    {
        try {
            $this->event = $event;

            $attributeName = $event->getAttributeName();
            if (in_array($attributeName, $this->getAvailableAttributes(), true)) {
                $event->setAttributeValue($this->getUserAttributeValue($event->getUserIdentifier(), $attributeName, $event->getAttributeValue()));
            }
        } finally {
            $this->event = null;
        }
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->event->getAttribute($attributeName, $defaultValue);
    }

    /*
     * @return string[]
     */
    abstract protected function getAvailableAttributes(): array;

    /**
     * @param mixed|null $attributeValue
     *
     * @return mixed|null
     */
    abstract protected function getUserAttributeValue(?string $userIdentifier, string $attributeName, $attributeValue);
}
