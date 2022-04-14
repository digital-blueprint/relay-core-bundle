<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Service;

use Dbp\Relay\CoreBundle\Event\LocalDataAwarePostEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LocalDataAwareEventDispatcher
{
    private $requestedAttributes;

    /** @var string */
    private $unqiueEntityName;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var string */
    private $eventName;

    public function __construct(string $unqiueEntityName, EventDispatcherInterface $eventDispatcher, string $eventName)
    {
        $this->unqiueEntityName = $unqiueEntityName;
        $this->eventDispatcher = $eventDispatcher;
        $this->eventName = $eventName;
    }

    public function initRequestedLocalDataAttributes(array $options)
    {
        $this->requestedAttributes = [];
        if ($include = $options['include'] ?? null) {
            $requestedLocalDataAttributes = explode(',', $include);

            foreach ($requestedLocalDataAttributes as $requestedLocalDataAttribute) {
                $requestedLocalDataAttribute = trim($requestedLocalDataAttribute);
                if (!empty($requestedLocalDataAttribute)) {
                    $requestedUniqueEntityName = null;
                    $requestedAttributeName = null;
                    if (!self::parseLocalDataAttribute($requestedLocalDataAttribute, $requestedUniqueEntityName, $requestedAttributeName)) {
                        throw new HttpException(400, sprintf("value of 'include' parameter has invalid format: '%s' (Example: 'ResourceName.attr,ResourceName.attr2')", $requestedLocalDataAttribute));
                    }

                    if ($this->unqiueEntityName === $requestedUniqueEntityName) {
                        $this->requestedAttributes[] = $requestedAttributeName;
                    }
                }
            }
            $this->requestedAttributes = array_unique($this->requestedAttributes);
        }
    }

    public function dispatch(LocalDataAwarePostEvent $event)
    {
        $event->setRequestedAttributes($this->requestedAttributes);

        $this->eventDispatcher->dispatch($event, $this->eventName);

        $remainingLocalDataAttributes = $event->getRemainingRequestedAttributes();
        if (!empty($remainingLocalDataAttributes)) {
            throw new HttpException(500, sprintf("the following local data attributes were not provided for resource '%s': %s", $this->unqiueEntityName, implode(', ', $remainingLocalDataAttributes)));
        }
    }

    private static function parseLocalDataAttribute(string $localDataAttribute, ?string &$entityUniqueName, ?string &$attributeName): bool
    {
        $parts = explode('.', $localDataAttribute);
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            return false;
        }
        $entityUniqueName = $parts[0];
        $attributeName = $parts[1];

        return true;
    }
}
