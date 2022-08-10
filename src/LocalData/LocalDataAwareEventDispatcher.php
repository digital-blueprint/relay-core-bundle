<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceMetadataFactory;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LocalDataAwareEventDispatcher
{
    /** @var array */
    private $queryParameters;

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
        $this->queryParameters = [];
        $this->requestedAttributes = [];
        $this->uniqueEntityName = self::getUniqueEntityName($resourceClass);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function init(array &$options)
    {
        $localIncludeParameter = LocalData::getIncludeParameter($options);
        if (!Tools::isNullOrEmpty($localIncludeParameter)) {
            $this->initIncludeParameters($localIncludeParameter);
        }

        $localQueryParameter = LocalData::getQueryParameter($options);
        if (!Tools::isNullOrEmpty($localQueryParameter)) {
            $this->initQueryParameters($localQueryParameter);
        }

        LocalData::removeOptions($options);
    }

    /**
     * Returns, whether the attribute with the given name was requested.
     */
    public function isAttributeRequested(string $attributeName): bool
    {
        return in_array($attributeName, $this->requestedAttributes, true);
    }

    /**
     * Checks if the given entity's local data attribute names matches the list of requested attributes this event dispatcher's entity (resource).
     * NOTE: The resource class of the entities must match.
     *
     * @param LocalDataAwareInterface $entity The entity whose local data attributes to check
     */
    public function checkRequestedAttributesIdentical(LocalDataAwareInterface $entity)
    {
        assert(self::getUniqueEntityName(get_class($entity)) === $this->uniqueEntityName);

        $availableAttributes = $entity->getLocalData() ? array_keys($entity->getLocalData()) : [];

        return count($this->requestedAttributes) === count($availableAttributes) &&
            empty(array_diff($this->requestedAttributes, $availableAttributes));
    }

    /**
     * Dispatches the given event. Default event-dispatch as provided by EventDispatcherInterface.
     */
    public function dispatch(LocalDataAwarePreEvent $preEvent, string $eventName): void
    {
        $this->eventDispatcher->dispatch($preEvent, $eventName);
    }

    /**
     * Dispatches the given pre-event. Implements the local query parameters logic.
     */
    public function dispatchPre(LocalDataAwarePreEvent $preEvent, string $eventName): void
    {
        $preEvent->setQueryParameters($this->queryParameters);
        $this->eventDispatcher->dispatch($preEvent, $eventName);
    }

    /**
     * Dispatches the given post-event. Implements the local include parameters logic.
     */
    public function dispatchPost(LocalDataAwarePostEvent $event, string $eventName): void
    {
        $event->setRequestedAttributes($this->requestedAttributes);

        $this->eventDispatcher->dispatch($event, $eventName);

        $remainingLocalDataAttributes = $event->getRemainingRequestedAttributes();
        if (!empty($remainingLocalDataAttributes)) {
            throw new ApiError(400, sprintf("the following requested local data attributes could not be provided for resource '%s': %s", $this->uniqueEntityName, implode(', ', $remainingLocalDataAttributes)));
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
     * Parses the local data request parameter and extracts the list of requested attributes for this event dispatcher's entity (resource).
     *
     * @param ?string $includeParameter The value of the 'include' parameter as passed to a GET-operation
     */
    private function initIncludeParameters(?string $includeParameter): void
    {
        $this->requestedAttributes = [];

        if (!Tools::isNullOrEmpty($includeParameter)) {
            $requestedAttributes = explode(',', $includeParameter);

            foreach ($requestedAttributes as $requestedAttribute) {
                $requestedAttribute = trim($requestedAttribute);
                if ($requestedAttribute !== '') {
                    $uniqueEntityName = null;
                    $uniqueAttributeName = null;
                    if (!$this->parseLocalDataAttribute($requestedAttribute, $uniqueEntityName, $uniqueAttributeName)) {
                        throw new ApiError(400, sprintf("value of '%s' parameter has invalid format: '%s' (Example: 'attr,ResourceName.attr2')", LocalData::INCLUDE_PARAMETER_NAME, $requestedAttribute));
                    }

                    if ($this->uniqueEntityName === $uniqueEntityName) {
                        $this->requestedAttributes[] = $uniqueAttributeName;
                    }
                }
            }
            $this->requestedAttributes = array_unique($this->requestedAttributes);
        }
    }

    private function initQueryParameters(string $queryParameter)
    {
        $localQueryParameters = explode(',', $queryParameter);

        foreach ($localQueryParameters as $localQueryParameter) {
            $localQueryParameter = trim($localQueryParameter);
            if ($localQueryParameter !== '') {
                $parameterKey = null;
                $parameterValue = null;
                $uniqueEntityName = null;
                $uniqueAttributeName = null;
                if (!$this->parseQueryParameterAssignment($localQueryParameter, $parameterKey, $parameterValue) ||
                    !$this->parseLocalDataAttribute($parameterKey ?? '', $uniqueEntityName, $uniqueAttributeName)) {
                    throw new ApiError(400, sprintf("'%s' parameter has invalid format: '%s' (Example: 'param1:val1,ResourceName.attr1:val2')", LocalData::QUERY_PARAMETER_NAME, $localQueryParameter));
                }

                if ($uniqueEntityName === $this->uniqueEntityName) {
                    $this->queryParameters[$parameterKey] = $parameterValue;
                }
            }
        }
    }

    /**
     * Parses a local data attribute of the form 'UniqueEntityName.attributeName'.
     * NOTE: Due to possible performance impact, there is currently no regex check for valid entity and attribute names (i.e. PHP type/variable names).
     *
     * @retrun true if $localDataAttribute complies with the local attribute format, false otherwise
     */
    private function parseQueryParameterAssignment(string $parameterAssignment, ?string &$parameter, ?string &$value): bool
    {
        $parameter = null;
        $value = null;

        $parts = explode(':', $parameterAssignment);

        if (count($parts) === 2) {
            $parameter = $parts[0];
            $value = $parts[1];
        }

        return !Tools::isNullOrEmpty($parameter) && !Tools::isNullOrEmpty($value);
    }

    /**
     * Parses a local data attribute of the form 'UniqueEntityName.attributeName'.
     * NOTE: Due to possible performance impact, there is currently no regex check for valid entity and attribute names (i.e. PHP type/variable names).
     *
     * @retrun true if $localDataAttribute complies with the local attribute format, false otherwise
     */
    private function parseLocalDataAttribute(string $localDataAttribute, ?string &$uniqueEntityName, ?string &$attributeName): bool
    {
        $uniqueEntityName = null;
        $attributeName = null;

        $parts = explode('.', $localDataAttribute);
        if (count($parts) === 1) {
            $uniqueEntityName = $this->uniqueEntityName;
            $attributeName = $parts[0];
        } elseif (count($parts) === 2) {
            $uniqueEntityName = $parts[0];
            $attributeName = $parts[1];
        }

        return !Tools::isNullOrEmpty($uniqueEntityName) && !Tools::isNullOrEmpty($attributeName);
    }
}
