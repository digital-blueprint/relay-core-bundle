<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceMetadataFactory;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LocalDataAwareEventDispatcher
{
    /** @var array */
    private $requestedAttributes;

    /** @var string */
    private $uniqueEntityName;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param string                   $resourceClass   The class name of the entity (resource) this event dispatcher is responsible for
     * @param EventDispatcherInterface $eventDispatcher The inner event dispatcher that this event dispatcher decorates
     */
    public function __construct(string $resourceClass, EventDispatcherInterface $eventDispatcher)
    {
        $this->requestedAttributes = [];
        $this->uniqueEntityName = self::getUniqueEntityName($resourceClass);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Parses the 'include' parameter and extracts the list of requested attributes for this event dispatcher's entity (resource).
     *
     * @param ?string $includeParameter The value of the 'include' parameter as passed to a GET-operation
     */
    public function initRequestedLocalDataAttributes(?string $includeParameter): void
    {
        $this->requestedAttributes = [];

        if (!empty($includeParameter)) {
            $requestedLocalDataAttributes = explode(',', $includeParameter);

            foreach ($requestedLocalDataAttributes as $requestedLocalDataAttribute) {
                $requestedLocalDataAttribute = trim($requestedLocalDataAttribute);
                if (!empty($requestedLocalDataAttribute)) {
                    $requestedUniqueEntityName = null;
                    $requestedAttributeName = null;
                    if (!self::parseLocalDataAttribute($requestedLocalDataAttribute, $requestedUniqueEntityName, $requestedAttributeName)) {
                        throw new HttpException(400, sprintf("value of 'include' parameter has invalid format: '%s' (Example: 'ResourceName.attr,ResourceName.attr2')", $requestedLocalDataAttribute));
                    }

                    if ($this->uniqueEntityName === $requestedUniqueEntityName) {
                        $this->requestedAttributes[] = $requestedAttributeName;
                    }
                }
            }
            $this->requestedAttributes = array_unique($this->requestedAttributes);
        }
    }

    /**
     * Checks if the given entity's local data attribute names matches the list of requested attributes this event dispatcher's entity (resource).
     * NOTE: The resource class of the entities must match.
     *
     * @param LocalDataAwareInterface $entity The entity whose local data attributes to check
     */
    public function checkRequestedAttributesIdentitcal(LocalDataAwareInterface $entity)
    {
        assert(self::getUniqueEntityName(get_class($entity)) === $this->uniqueEntityName);

        dump(array_keys($entity->getLocalData() ?? []));
        dump($this->requestedAttributes);

        $availableAttributes = $entity->getLocalData() ? array_keys($entity->getLocalData()) : [];

        return count($this->requestedAttributes) === count($availableAttributes) &&
            empty(array_diff($this->requestedAttributes, $availableAttributes));
    }

    /**
     * Dispatches the given event.
     */
    public function dispatch(LocalDataAwareEvent $event, string $eventName): void
    {
        $event->setRequestedAttributes($this->requestedAttributes);

        $this->eventDispatcher->dispatch($event, $eventName);

        $remainingLocalDataAttributes = $event->getRemainingRequestedAttributes();
        if (!empty($remainingLocalDataAttributes)) {
            throw new HttpException(500, sprintf("the following local data attributes were not provided for resource '%s': %s", $this->uniqueEntityName, implode(', ', $remainingLocalDataAttributes)));
        }
    }

    /**
     * Returns the unique API resource name, i.e. short name, of this entity. For this to work, the ApiResource annotation
     * of the entity has to include a non-empty 'shortName' attribute.
     *
     * @throws ApiError if the ApiResource annotation of $resourceClass doesn't have a non-empty 'shortName' attribute
     */
    public static function getUniqueEntityName(string $resourceClass): string
    {
        $resourceMetadataFactory = new AnnotationResourceMetadataFactory(new AnnotationReader());
        try {
            $resourceMetadata = $resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $exc) {
            throw new ApiError(500, $exc->getMessage());
        }

        $uniqueName = $resourceMetadata->getShortName() ?? '';
        if (empty($uniqueName)) {
            throw new ApiError(500, sprintf("'shortName' attribute missing in ApiResource annotation of resource class '%s'", $resourceClass));
        } elseif (str_contains($uniqueName, '.') || str_contains($uniqueName, ',')) {
            throw new ApiError(500, sprintf("'shortName' attribute of resource class '%s' must not contain '.' or ',' characters: '%s'", $resourceClass, $uniqueName));
        }

        return $uniqueName;
    }

    /**
     * Parses a local data attribute of the form 'UniqueEntityName.attributeName'.
     * NOTE: Due to possible performance impact, there is currently no regex check for valid entity and attribute names (i.e. PHP type/variable names).
     *
     * @retrun true if $localDataAttribute complies with the local attribute format, false otherwise
     */
    private static function parseLocalDataAttribute(string $localDataAttribute, ?string &$uniqueEntityName, ?string &$attributeName): bool
    {
        $parts = explode('.', $localDataAttribute);
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            return false;
        }
        $uniqueEntityName = $parts[0];
        $attributeName = $parts[1];

        return true;
    }
}
